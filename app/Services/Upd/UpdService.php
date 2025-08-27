<?php

namespace App\Services\Upd;

use App\Jobs\UpdJob;
use App\Models\Order;
use App\Models\OrderBids;
use App\Models\OrderItems;
use App\Models\Upd;
use App\Models\UpdItems;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdService extends Service
{
    /**
     * Фиксация упд на основании заказа
     *
     * @param array $inp
     * @throws \Exception
     * @return array|JsonResponse
     */
    public function fixUpdOfOrder(array $inp): JsonResponse|array
    {

        // Проверяем наличие УПД
        $upd = (new Upd())->getUpd([
            'num' => $inp['code'],
        ]);

        if (!empty($upd)) {
            return apiError('УПД с таким номер уже найдено ...');
        }

        // Заказ
        $order = (new Order())->getOrderFast(['code' => $inp['code']]);

        if (empty($order)) {
            return apiError('Заказ не найден');
        }

        if (empty($order->company_inn)) {
            return apiError('ИНН компании не указано');
        }

        // Если есть дата реализации
        !empty($order->closed_at) ?
            $inp['date'] = Carbon::parse($order->closed_at)->format('Y-m-d') :
            $inp['date'] = Carbon::now()->format('Y-m-d');

        // Фильтруем и готовим товары
        $items = $this->prepareOrderItems($order->code);

        if (empty($items)) {
            return apiError('Нет выбранных товаров внутри УПД');
        }

        return $this->insert([
            'inn'      => $order->company_inn,
            'num'      => $order->code,
            'type'     => Upd::TYPE_SALE,
            'date'     => $inp['date'],
            'setting'  => $order->setting,
            'sum'      => $order->sum ,
            'kolvo'    => $order->kolvo,
            'delivery' => $inp['delivery'] ?? null,
            'items'    => $items,
        ]);

    }

    /**
     * Фиксация отгрузки
     *
     * @param array $inp
     * @throws \Exception
     * @return array|JsonResponse
     */
    public function fixUpd(array $inp): JsonResponse|array
    {

        $inp['date'] = Carbon::parse($inp['date'])->format('Y-m-d');

        // Фильтруем и готовим товары
        $items = $this->prepareUpdItems($inp['upd_items']);

        if (empty($items)) {
            return apiError('Нет выбранных товаров внутри УПД');
        }

        $sumItemPurchase = array_sum(array_column($items, 'sum'));
        $countItem       = array_sum(array_column($items, 'item_kolvo'));

        // Если суммы не равны
        if (round($sumItemPurchase, 2) != round($inp['sum'], 2)) {
            return apiError("Сумма УПД {$inp['sum']} р. не равна сумме закупов товаров {$sumItemPurchase} р.");
        }

        return $this->insert([
            'inn'      => $inp['inn'],
            'num'      => $inp['num'],
            'type'     => Upd::TYPE_PURCHASE,
            'date'     => $inp['date'],
            'setting'  => $inp['setting'],
            'sum'      => $sumItemPurchase,
            'kolvo'    => $countItem,
            'delivery' => $inp['delivery'] ?? null,
            'items'    => $items,
        ]);

    }

    /**
     * Добавляем УПД в базу
     *
     * @param array $inp
     * @return mixed
     */
    public function insert(array $inp): mixed
    {

        return DB::transaction(function () use ($inp) {

            // Ищем или создаем УПД
            $upd = (new Upd())->getUpd([
                'inn'     => $inp['inn'],
                'num'     => $inp['num'],
                'type'    => $inp['type'],
                'date'    => $inp['date'],
                'setting' => $inp['setting'],
            ]) ?? (new Upd())->insert([
                'inn'     => $inp['inn'],
                'num'     => $inp['num'],
                'type'    => $inp['type'],
                'date'    => $inp['date'],
                'sum'     => $inp['sum'],
                'kolvo'   => $inp['kolvo'],
                'setting' => $inp['setting'],
            ]);

            if (!$upd) {
                throw new \Exception('Не получилось добавить УПД');
            }

            // Добавляем товары
            foreach ($inp['items'] as $item) {

                $insert = (new UpdItems())->insert([
                    'upd'        => $upd->id,
                    'num'        => $inp['num'],
                    'code'       => $item['code'],
                    'item'       => $item['item'],
                    'item_price' => $item['item_price'],
                    'item_kolvo' => $item['item_kolvo'],
                ]);

                if (!$insert) {
                    throw new \Exception('Не получилось добавить товар в УПД');
                }

                // Если УПД продажи
                if ($inp['type'] == UPD::TYPE_SALE) {
                    continue;
                }

                $update = (new OrderBids())->updateUPD([
                    'id'  => $item['bid_id'],
                    'upd' => $upd->id,
                ]);

                if (!$update) {
                    throw new \Exception('Не получилось обновить id УПД в товаре заказа');
                }

            }

            // Доставка
            if (!empty($inp['delivery'])) {

                $insert = (new UpdItems())->insert([
                    'upd'        => $upd->id,
                    'num'        => $inp['num'],
                    'code'       => null,
                    'item'       => Upd::ITEM_ID_DELIVERY,
                    'item_price' => $inp['delivery'],
                    'item_kolvo' => 1,
                ]);

                if (!$insert) {
                    throw new \Exception('Не получилось добавить услугу доставки в УПД');
                }
            }

            (new Upd())->updateSumAndKolvo(['id' => $upd->id]);

            // Выгружаем УПД в 1С
            dispatch(new UpdJob($upd->id));

            return $upd;

        });
    }

    /**
     * Удаление УПД
     *
     * @param array $inp
     * @return mixed
     */
    public function remove(array $inp): mixed
    {

        return DB::transaction(function () use ($inp): void {

            $delete = (new Upd())->remove(['id' => $inp['id']]);

            if (!$delete) {
                throw new \Exception('Не получилось удалить УПД');
            }

            // Товары в УПД
            $items = (new UpdItems())->getItems(['upd' => $inp['id']]);

            foreach ($items as $item) {

                // Удаление товара из УПД
                $delete = (new ItemService())->remove([
                    'id' => $item->id,
                ]);

                if (!$delete) {
                    throw new \Exception('Не получилось удалить УПД');
                }

            }

        });

    }

    /**
     * Подготовливаем выбранные товары для добавления в УПД
     *
     * @param array $items
     * @return array
     */
    public function prepareUpdItems(array $items): array
    {

        $result = [];

        foreach ($items as $item) {

            if (empty($item['checkbox'])) {
                continue;
            }

            $item['bid_id'] = $item['id'];

            $item['item_kolvo'] = $item['item_kolvo'] ?: $item['item_kolvo_start'];
            $item['item_price'] = (float) str_replace(',', '.', $item['item_purchase'] ?? 0);

            $item['sum'] = $item['item_price'] * $item['item_kolvo'];

            $result[] = $item;
        }

        return $result;

    }

    /**
     * Подготовливаем товары из заказа для добавления в УПД
     *
     * @param int $orderCode
     * @return array
     */
    public function prepareOrderItems(int $orderCode): array
    {

        $result = [];

        // Товары в заказе
        $items = (new OrderItems())->getItems(['code' => $orderCode]);

        foreach ($items as $item) {

            if (empty($item->item_kolvo)) {
                continue;
            }

            if (empty($item->item_price)) {
                continue;
            }

            $result[] = [
                'code'       => $item->code,
                'item'       => $item->item,
                'item_price' => $item->item_price,
                'item_kolvo' => $item->item_kolvo,
            ];

        }

        return $result;

    }
}
