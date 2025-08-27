<?php

namespace App\Services\Finance\EncashmentStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Services\Finance\EncashmentStrategy\EncashmentStrategyInterface;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;

class UserToUserEncashment extends FinanceService implements EncashmentStrategyInterface
{
    /**
     * Инкассация (передача д/c) между магазинами
     *
     * @param array $data
     * @return JsonResponse
     */
    public function handle(array $data): JsonResponse
    {

        $auth = auth()->user();

        // Добавляем операцию Передача д/c - расход
        $insert = (new Finance())->insert([
            'store_id'             => self::STORE_ID_OFFICE,
            'store_cash_id'        => self::STORE_ID_OFFICE,
            'cashbox'              => 2,
            'type'                 => 'расход',
            'paycash'              => 1,
            'date'                 => $data['date'],
            'title'                => $auth->name,
            'cat_id'               => 40, // Передача д/c
            'sum'                  => $data['sum'],
            'user_id'              => $auth->id,
            'user_pay_id'          => $data['user'],
            'view'                 => 0,
            'moder_store'          => self::STORE_ID_OFFICE,
            'moder_store_status'   => self::STATUS_ACTIVE,
            'moder_manager'        => $data['user_cash'],
            'moder_manager_status' => self::STATUS_ACTIVE,

        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        $insert = (new Finance())->insert([
            'store_id'             => self::STORE_ID_OFFICE,
            'store_cash_id'        => self::STORE_ID_OFFICE,
            'cashbox'              => 2,
            'type'                 => 'приход',
            'paycash'              => 1,
            'date'                 => $data['date'],
            'title'                => $auth->name,
            'cat_id'               => 40, // Передача д/c
            'sum'                  => $data['sum'],
            'user_id'              => $auth->id,
            'user_pay_id'          => $data['user'],
            'view'                 => 0,
            'moder_store'          => self::STORE_ID_OFFICE,
            'moder_store_status'   => self::STATUS_ACTIVE,
            'moder_manager'        => $data['user'],
            'moder_manager_status' => self::STATUS_ACTIVE,

        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess($insert);

    }
}
