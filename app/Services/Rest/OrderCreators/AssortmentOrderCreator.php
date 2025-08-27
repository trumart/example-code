<?php

namespace App\Services\Rest\OrderCreators;

use App\Models\Item;
use App\Models\Order;
use App\Services\Rest\OrderDecorators\NoticeOrderService;
use Carbon\Carbon;

class AssortmentOrderCreator extends BaseOrderCreator
{
    protected function beforeCreate(): void
    {

        $this->inp['status']     = 'Новый';
        $this->inp['user_fio']   = 'С остатков в Магазин';
        $this->inp['user_phone'] = 89116002041;
        $this->inp['user']       = self::USER_SYSTEM_ID;
        $this->inp['topay']      = 0;
        $this->inp['moder']      = self::STATUS_NOACTIVE;

        // Сумма заказа
        $this->inp['sum'] = ($this->inp['price'] * $this->inp['kolvo']);

        // Товар
        $item = (new Item())->getItem(['id' => $this->rest->item], true);

        // Товар
        $this->inp['goods'][] = [
            'status'              => 'ожидает обработки',
            'item'                => $this->rest->item,
            'store_sender'        => $this->rest->store,
            'item_title'          => $item->title,
            'item_kolvo'          => $this->inp['kolvo'],
            'item_price'          => $this->inp['price'],
            'item_purchase'       => $this->rest->purchase,
            'item_purchase_start' => $this->rest->purchase,
            'delivery_date'       => Carbon::now()->format('Y-m-d'),
            'row'                 => $this->rest->row   ?? null,
            'rack'                => $this->rest->rack  ?? null,
            'shelf'               => $this->rest->shelf ?? null,
            'cell'                => $this->rest->cell  ?? null,
            'moder'               => 0,
            'pay'                 => 0,
            'drive'               => 0,
            'received'            => 0,
            'bid_setting_id'      => $this->rest->setting,
            'delivered_at'        => null,
        ];

    }

    protected function afterCreate(Order $order): void
    {

        // Уведомления
        (new NoticeOrderService())->handle([
            'store_id'        => $order->store,
            'store_sender_id' => $this->rest->store,
            'item'            => $this->inp['goods'][0]['item'],
            'item_title'      => $this->inp['goods'][0]['item_title'],
        ]);

    }
}
