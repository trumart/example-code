<?php

namespace App\Services\Rest;

use App\Models\ItemPrice;
use App\Models\Rest;
use App\Models\Store;
use App\Models\UserWork;
use App\Services\Order\SettingService;
use App\Services\Rest\OrderCreators\AssortmentOrderCreator;
use App\Services\Rest\OrderCreators\ClientOrderCreator;
use App\Services\Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class OrderService extends Service
{
    protected ?Authenticatable $auth;

    public function __construct()
    {

        $this->auth = auth()->user();

    }

    /**
     * Создание заказа
     *
     * @param array $inp
     * @return int
     */
    public function orderCreate(array $inp): int|JsonResponse
    {

        // Остаток
        $rest = (new Rest())->getRest(['id' => $inp['id']]);

        if (($check = $this->isAvailableForSale($rest, $inp)) !== true) {
            return $check;
        }

        // Определение закупа
        $rest = $this->determineRestPurchase($rest, $inp['kolvo']);

        // Склад остатка
        $storePriceId = (new Store())->getStore(['id' => $rest->store])?->store_price;

        // Цена продажи
        $inp['price'] = $this->determinePriceSale($rest, $storePriceId, $inp['type_price']);

        // Склад получатель
        $inp['store_id'] = $this->determineStoreSale($rest);

        // Организация продажи
        $inp['setting_id'] = (new SettingService())->determineSaleSettingForRest($inp['pay_type'], $rest->id, $inp['store_id']);

        $orderCreator = !empty($inp['autoorder'])
            ? new AssortmentOrderCreator()
            : new ClientOrderCreator();

        $order = $orderCreator->create($rest, $inp);

        return $order->code;

    }

    /**
     * Проверяем возможность продажи товара
     *
     * @param Rest $rest
     * @param array $inp
     * @return bool|JsonResponse
     */
    private function isAvailableForSale(Rest $rest, array $inp): bool|JsonResponse
    {

        if (!in_array($rest->status, ['в наличии', 'на проверке', 'ожидает поступления'])) {
            return response()->apiError('По данному товару нельзя создать заказ / оформить продажу');
        }

        // Проверяем кол-во на остатках
        $restCount = (new Rest())->sumRest([
            'item'   => $rest->item,
            'store'  => $rest->store,
            'status' => ['в наличии', 'на проверке']
        ]);

        if ($inp['kolvo'] > $restCount->count) {
            return response()->apiError('На остатках не найдено товара в требуемом кол-ве ...');
        }

        // Открыта ли смена сотрудника?
        $work = (new UserWork())->getUserWork([
            'user' => $this->auth->id,
            'date' => date('Y-m-d')
        ]);

        if (empty($work)) {
            return response()->apiError('Извини, но у тебя не открыта смена');
        }

        // Если заказ на пополнение ассортимента
        if (empty($inp['autoorder'])) {

            // ФИО клиента
            if (empty($inp['user_fio'])) {
                return response()->apiError('Укажи Имя клиента');
            }

            // Обработка телефона
            if (empty($inp['user_phone'])) {
                return response()->apiError('Укажите телефон клиента');
            }

        }

        if (!empty($inp['topay']) && empty($inp['pay_type'])) {
            return response()->apiError('Выбери вид оплаты или укажи сумму принято от клиента 0');
        }

        return true;

    }

    /**
     * Определение стоимости продажи товара
     *
     * @param Rest $rest
     * @param Store $store
     * @param string $priceType
     * @return int|JsonResponse
     */
    public function determinePriceSale(Rest $rest, int $storePriceId, string $priceType): int|JsonResponse
    {
        $priceSale = match($priceType) {
            'rest' => $rest->offline_price,
            'site', 'rozn' => (new ItemPrice())->getPrice([
                'item'  => $rest->item,
                'store' => $priceType === 'site' ? $storePriceId : 136,
            ])->price,
            default => response()->apiError('Нет данных для получения цены')
        };

        if (empty($priceSale)) {
            return response()->apiError('Не определена стоимость товара, вы выбрали по какой цене хотите продать?');
        }

        if ($priceSale <= $rest->purchase) {
            return response()->apiError(
                'Цена дешевле закупочной стоимости. ' .
                'По цене сайта можно только заказать позицию. Обратитесь к руководству.'
            );
        }

        return (int) $priceSale;
    }

    /**
     * Определение склада получателя
     *
     * @param Rest $rest
     * @return int
     */
    public function determineStoreSale(Rest $rest): int
    {
        return (int) ($this->auth->store ?? $rest->store);
    }

    /**
     * Определения закупа по товару
     *
     * @param Rest $rest
     * @param array $inp
     * @return Rest
     */
    private function determineRestPurchase(Rest $rest, int $quantity): Rest
    {

        if ($quantity <= 1) {
            return $rest;
        }

        $avg = (new Rest())->avgPurchaseLimit([
            'item'   => $rest->item,
            'store'  => $rest->store,
            'status' => ['в наличии', 'на проверке'],
            'limit'  => $quantity,
        ]);

        $rest->purchase = $avg ?: $rest->purchase;

        return $rest;
    }
}
