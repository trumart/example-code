<?php



namespace App\Services\Pay;

use App\Models\Pay;

class PayService
{
    /**
     * Оплаты и предоплаты по заказам
     *
     * @param array $inp ['date' => string, 'store' => int, 'type' => string]
     * @return array{
     * *     list: [
     * *         pay: \Illuminate\Support\Collection,
     * *         pre: \Illuminate\Support\Collection
     * *     ],
     * *     sum: [
     * *         now: float,
     * *         pre: float
     * *     ]
     * * }
    */
    public function getAllPay(array $inp): array
    {

        // Оплаты - список
        $payList = (new Pay())->getAllPay(array_merge($inp, ['order_status' => 'Закрыт']), 'list');
        $preList = (new Pay())->getAllPay(array_merge($inp, ['order_nostatus' => 'Закрыт']), 'list');

        return [
            'list' => [
                'pay' => $payList,
                'pre' => $preList,
            ],
            'sum' => [
                'pay' => $payList->sum('amount_deposit'),
                'pre' => $preList->sum('amount_deposit'),
            ],
        ];

    }
}
