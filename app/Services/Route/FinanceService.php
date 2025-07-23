<?php



namespace App\Services\Route;

use App\Models\Finance\Finance;
use App\Models\OrderItems;
use App\Models\RouteItem;
use App\Models\RouteParam;
use App\Services\Service;
use Carbon\Carbon;

class FinanceService extends Service
{
    /**
     * Генерация расходов по логистике с привязкой к подразделениям, с учетом доли закупочной стоимости товаров в машине
     *
     * @param array $auth, $inp
     * @return string
     */
    public function generateFinanceCostFromRoute($auth, $inp = [])
    {

        // 30 Дней
        $inp['date_start'] ??= Carbon::now()->subDays(30);

        $routesParam = (new RouteParam())->getParams($inp);

        foreach ($routesParam as $routeParam) {

            if (empty($routeParam->price)) {
                continue;
            }

            $arrStore = [];

            // Товары в машине
            $items = (new RouteItem())->getItems([
               'route_param' => $routeParam->id,
            ]);

            foreach ($items as $k => $item) {

                // Товар в заказе
                $orderItem = (new OrderItems())->getItem(['id' => $item->order_item]);

                if (!isset($arrStore[$item->recipient])) {
                    $arrStore[$item->recipient] = 0;
                }

                if (empty($orderItem->item_kolvo)) {
                    $orderItem->item_kolvo = $orderItem->item_kolvo_start;
                }

                $arrStore[$item->recipient] += $orderItem->item_purchase * $orderItem->item_kolvo;

            }

            // Склады
            foreach ($arrStore as $storeId => $sum) {

                $costDiffPr    = $sum / $routeParam->purchase_items;
                $costDiffPrice = $routeParam->price * $costDiffPr;

                $costDiff[$storeId] = round($costDiffPrice, 2);

                if (empty($costDiff[$storeId])) {
                    continue;
                }

                $finance = (new Finance())->getOperation([
                    'date'  => $routeParam->date,
                    'store' => $storeId,
                    'title' => 'Поездка ' . $routeParam->id . ' %',
                    'cat'   => 69, // Логистика (генерируемая для отчетов)
                ]);

                if (!empty($finance)) {

                    if ($finance->sum == $costDiff) {
                        continue;
                    }

                    $update = $finance->edit([
                        'id'  => $finance->id,
                        'sum' => $costDiff[$storeId],
                    ]);

                    if (!$update) {
                        echo '- ошибка обновления стоимости логистики';
                    }

                    echo '- стоимость логистики обновлена';
                    continue;

                }

                $insert = (new Finance())->insert([
                    'user_id'              => self::USER_SYSTEM_ID,
                    'user_pay_id'          => 0,
                    'date'                 => $routeParam->date,
                    'store_id'             => $storeId,
                    'store_cash_id'        => self::STORE_ID_OFFICE,
                    'cashbox'              => 2,
                    'paycash'              => 0,
                    'type'                 => 'расход',
                    'title'                => 'Поездка ' . $routeParam->id . ' от ' . Carbon::parse($routeParam->date)->format('d.m.Y'),
                    'cat_id'               => 69, // Логистика (генерируемая для отчетов)
                    'sum'                  => $costDiff[$storeId],
                    'view'                 => 0,
                    'moder_store'          => self::STORE_ID_OFFICE,
                    'moder_store_status'   => self::STATUS_ACTIVE,
                    'moder_manager'        => self::USER_SYSTEM_ID,
                    'moder_manager_status' => self::STATUS_ACTIVE,
                ]);

                if (!$insert) {
                    echo '- ошибка добавления операции стоимости логистики';
                }

                echo '- операция стоимости логистики добавлена';

            }
        }

        // Нельзя return так как в console прерываются следующие методы
        echo 'continue';

    }
}
