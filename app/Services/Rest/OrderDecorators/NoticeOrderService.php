<?php

namespace App\Services\Rest\OrderDecorators;

use App\Models\Notice;
use App\Models\Tasks;
use App\Services\Rest\Contracts\OrderAfterCreateServiceContract;
use App\Services\Service;

class NoticeOrderService extends Service implements OrderAfterCreateServiceContract
{
    public function handle(array $inp): void
    {

        // Если склад заказчик != склад отправитель
        if ($inp['store_id'] != $inp['store_sender_id']) {

            // Уведомление отправителю
            (new Notice())->add([
                'date'    => null,
                'user'    => 0,
                'store'   => $inp['store_sender_id'],
                'access'  => null,
                'title'   => 'Перемещение',
                'type'    => 'warning',
                'message' => "Подтвердите перемещение! Подготовьте к отправке со следующей машиной: {$inp['item_title']}",
                'url'     => route('moves.index'),
            ]);

            return;

        }

        // Если склад = офис или склад
        if (in_array($inp['store_id'], [self::STORE_ID_OFFICE, self::STORE_ID_SKLAD])) {
            return;
        }

        // Создаем задачу
        (new Tasks())->insert(auth()->user(), [
            'store'       => $inp['store_id'],
            'user'        => null,
            'cat'         => 'task',
            'date'        => date('Y-m-d'),
            'date_finish' => null,
            'type'        => 'Задача',
            'title'       => 'Выкладка ассортимента',
            'text'        => "Вы продали товар с остатков {$inp['item_title']}, а теперь нужно поправить рядом стоящий товар согласно правилам выкладки ассортимента, что бы не оставалось пустых мест (дырок)",
            'code'        => null,
            'item'        => $inp['item'],
            'url'         => null,
            'price'       => null,
            'moder_user'  => self::USER_SYSTEM_ID,
        ]);

    }
}
