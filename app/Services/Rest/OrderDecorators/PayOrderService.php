<?php

namespace App\Services\Rest\OrderDecorators;

use App\Services\Pay\PayService;
use App\Services\Rest\Contracts\OrderAfterCreateServiceContract;
use App\Services\Service;

class PayOrderService extends Service implements OrderAfterCreateServiceContract
{
    public function handle(array $inp): void
    {

        if (empty($inp['topay'])) {
            return;
        }

        // Добавляем оплату
        (new PayService())->insert([
            'order_id'       => $inp['order_id'],
            'payment_type'   => $inp['pay_type'],
            'status'         => 'Оплачено',
            'amount'         => $inp['topay'],
            'amount_deposit' => $inp['topay'],
            'moder'          => self::STATUS_NOACTIVE,
            'payment_accept' => in_array($inp['pay_type'], ['наличные', 'банковской картой']),
        ]);

    }
}
