<?php

namespace App\Services\Rest\OrderCreators;

use App\Models\Order;
use App\Models\Rest;
use App\Services\Order\OrderService;
use App\Services\Service;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class BaseOrderCreator extends Service
{
    protected ?Authenticatable $auth;

    protected Rest $rest;

    protected array $inp;

    public function __construct()
    {

        $this->auth = auth()->user();
    }

    final public function create(Rest $rest, array $inp): Order
    {

        // Остаток
        $this->rest = $rest;
        $this->inp  = $inp;

        $this->beforeCreate();

        $order = $this->saveOrder();

        $this->afterCreate($order);

        return $order;

    }

    /**
     * Создаем заказ
     *
     * @return Order
     */
    protected function saveOrder(): Order
    {

        $today = now()->toDateString();

        return (new OrderService())->insert([
            'status'             => $this->inp['status'],
            'user'               => $this->inp['user'],
            'user_fio'           => $this->inp['user_fio'],
            'user_phone'         => $this->inp['user_phone'],
            'store_id'           => $this->inp['store_id'],
            'store_report_id'    => $this->inp['store_id'],
            'delivery_datestart' => $today,
            'delivery_date'      => $today,
            'pay_type'           => $this->inp['pay_type'],
            'kolvo'              => $this->inp['kolvo'],
            'sum'                => $this->inp['sum'],
            'moder'              => $this->inp['moder'],
            'moder_user'         => $this->auth->id,
            'setting_id'         => $this->inp['setting_id'],
            'accepted_at'        => $today,
            'goods'              => $this->inp['goods'],
        ]);

    }

    abstract protected function beforeCreate(): void;

    abstract protected function afterCreate(Order $order): void;
}
