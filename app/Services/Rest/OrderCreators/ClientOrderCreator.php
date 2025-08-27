<?php

namespace App\Services\Rest\OrderCreators;

use App\Models\Functions;
use App\Models\Item;
use App\Models\Order;
use App\Services\Rest\OrderDecorators\AccountOrderService;
use App\Services\Rest\OrderDecorators\InstallationOrderService;
use App\Services\Rest\OrderDecorators\NoticeOrderService;
use App\Services\Rest\OrderDecorators\PayOrderService;
use App\Services\Rest\OrderDecorators\PutAwayRestService;
use App\Services\Rest\OrderDecorators\ReSaleOrganizationService;
use Carbon\Carbon;

class ClientOrderCreator extends BaseOrderCreator
{
    protected function beforeCreate(): void
    {

        $this->inp['user_phone'] = (new Functions())->preparePhone($this->inp['user_phone']);

        // Если нет данных кто оформляет
        $this->inp['user'] = $this->inp['user'] ?? $this->auth->id;

        // Сумма заказа
        $this->inp['sum'] = ($this->inp['price'] * $this->inp['kolvo']);

        $statusActive = [
            'moder'    => self::STATUS_ACTIVE,
            'pay'      => self::STATUS_ACTIVE,
            'drive'    => self::STATUS_ACTIVE,
            'received' => self::STATUS_ACTIVE,
        ];

        $statusNoActive = [
            'moder'    => self::STATUS_NOACTIVE,
            'pay'      => self::STATUS_NOACTIVE,
            'drive'    => self::STATUS_NOACTIVE,
            'received' => self::STATUS_NOACTIVE,
        ];

        if ($this->auth->store != $this->rest->store) {

            $this->inp['status']      = 'Подтвержден';
            $this->inp['status_item'] = 'ожидает обработки';
            $this->inp                = array_merge($this->inp, $statusNoActive);

        } else {

            $this->inp['status']      = 'Поступил';
            $this->inp['status_item'] = 'поступил';
            $this->inp                = array_merge($this->inp, $statusActive);

        }

        // Товар
        $item = (new Item())->getItem(['id' => $this->rest->item], true);

        // Товар
        $this->inp['goods'][] = [
            'status'              => $this->inp['status_item'],
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
            'moder'               => $this->inp['moder'],
            'pay'                 => $this->inp['pay'],
            'drive'               => $this->inp['drive'],
            'received'            => $this->inp['received'],
            'bid_setting_id'      => $this->rest->setting,
            'delivered_at'        => $this->inp['status_item'] === 'поступил' ? Carbon::now()->format('Y-m-d') : null,
        ];

    }

    protected function afterCreate(Order $order): void
    {

        // Сборка товара
        (new InstallationOrderService())->handle([
            'code'               => $order->code,
            'store'              => $order->store,
            'installation_price' => $this->rest->exhibition_price ?? 0,
            'installation_date'  => $this->rest->created_at,
        ]);

        // Регистрация пользователя
        (new AccountOrderService())->handle([
            'order_reg'  => $this->inp['order_reg'],
            'order_id'   => $order->id,
            'user_phone' => $this->inp['user_phone'],
            'user_fio'   => $this->inp['user_fio'],
        ]);

        // Убираем с остатков
        (new PutAwayRestService())->handle([
            'code'            => $order->code,
            'order_moder'     => $order->moder,
            'store_id'        => $order->store,
            'store_sender_id' => $this->rest->store,
            'rest_id'         => $this->rest->id,
            'rest_count'      => $this->rest->count,
            'quantity'        => $this->inp['kolvo'],
            'item'            => $this->inp['goods'][0]['item'],
        ]);

        // Перепродажа
        (new ReSaleOrganizationService())->handle([
            'code' => $order->code,
        ]);

        // Оплата
        (new PayOrderService())->handle([
            'topay'    => $this->inp['topay'],
            'order_id' => $order->id,
            'pay_type' => $this->inp['pay_type'],
        ]);

        // Уведомления
        (new NoticeOrderService())->handle([
            'store_id'        => $order->store,
            'store_sender_id' => $this->rest->store,
            'item'            => $this->inp['goods'][0]['item'],
            'item_title'      => $this->inp['goods'][0]['item_title'],
        ]);

    }
}
