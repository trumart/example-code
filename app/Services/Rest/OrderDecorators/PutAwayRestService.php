<?php

namespace App\Services\Rest\OrderDecorators;

use App\Models\ItemPriceHand;
use App\Models\Rest;
use App\Services\Rest\Contracts\OrderAfterCreateServiceContract;
use App\Services\Service;
use Carbon\Carbon;

class PutAwayRestService extends Service implements OrderAfterCreateServiceContract
{
    public function handle(array $inp): void
    {

        $auth = auth()->user();

        // Удаление ручной цены
        (new ItemPriceHand())->removeAuto(['item' => $inp['item']]);

        // Если на остатках 1 шт
        if ($inp['rest_count'] > 1) {

            // Уменьшаем кол-во на 1 шт
            (new Rest())->updateCount($auth, [
                'id'    => $inp['rest_id'],
                'count' => ($inp['rest_count'] - 1),
            ]);

            return;

        }

        // Если склад заказчик = склад отправитель и p подтверждена
        if ($auth->store == $inp['store_sender_id'] && $inp['order_moder'] === self::STATUS_ACTIVE) {

            // Убираем с остатков
            (new Rest())->sale($auth, [
                'id'       => $inp['rest_id'],
                'code_new' => $inp['code'],
                'status'   => 'продан',
                'datesale' => Carbon::now(),
            ]);

            // Если кол-во больше 1 шт
            if ($inp['quantity'] > 1) {

                // Убираем с остатков
                $rests = (new Rest())->getRests([
                    'item'   => $inp['item'],
                    'store'  => $inp['store_sender_id'],
                    'status' => ['в наличии', 'на проверке'],
                ]);

                foreach ($rests as $k => $rest) {

                    // Убираем с остатков
                    (new Rest())->sale($auth, [
                        'id'       => $rest->id,
                        'code_new' => $inp['code'],
                        'status'   => 'продан',
                        'datesale' => Carbon::now(),
                    ]);

                    // Ключ с 0, прибавляем 2, т.к. 1 уже списан
                    if ($inp['quantity'] == ($k + 2)) {
                        break;
                    }

                }
            }

            return;

        }

        // По какому заказу продан и дата продажи
        Rest::where('id', $inp['rest_id'])->update([
            'code_new' => $inp['code'],
            'datesale' => Carbon::now(),
        ]);

    }
}
