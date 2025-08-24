<?php

namespace App\Services\Finance\EncashmentStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Models\StoreWork;
use App\Services\Finance\EncashmentStrategy\EncashmentStrategyInterface;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;

class StoreToStoreEncashment extends FinanceService implements EncashmentStrategyInterface
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

        if (empty($data['store'])) {
            return response()->apiError('Код склада не передан');
        }

        // Открыта ли смена подразделения, кому отдают?
        $checkStore = (new StoreWork())->checkStoreWorkDate($data['store'], date('Y-m-d'));

        if (empty($checkStore)) {
            return response()->apiError('Воу, Воу, Погоди смена подразделения кому передаешь закрыта, я не могу разрешить добавить операцию');
        }

        // Добавляем операцию Передача д/c - расход
        $insert = (new Finance())->insert([
            'store_id'             => $auth->store ?? self::STORE_ID_OFFICE,
            'store_cash_id'        => $auth->store ?? self::STORE_ID_OFFICE,
            'cashbox'              => 2,
            'type'                 => 'расход',
            'paycash'              => 1,
            'date'                 => $data['date'],
            'title'                => 'Инкассация из магазина в магазин',
            'cat_id'               => 40, // Передача д/c
            'sum'                  => $data['sum'],
            'user_id'              => $auth->id,
            'user_pay_id'          => 0,
            'view'                 => 0,
            'moder_store'          => $auth->store ?? self::STORE_ID_OFFICE,
            'moder_store_status'   => self::STATUS_ACTIVE,
            'moder_manager'        => self::USER_SYSTEM_ID,
            'moder_manager_status' => self::STATUS_ACTIVE,
        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Добавляем операцию Передача д/c - расход
        $insert = (new Finance())->insert([
            'store_id'             => $data['store'],
            'store_cash_id'        => $data['store'],
            'cashbox'              => 2,
            'type'                 => 'приход',
            'paycash'              => 1,
            'date'                 => $data['date'],
            'title'                => 'Инкассация из магазина в магазин',
            'cat_id'               => 40, // Передача д/c
            'sum'                  => $data['sum'],
            'user_id'              => $auth->id,
            'user_pay_id'          => 0,
            'view'                 => 0,
            'moder_store'          => $data['store'],
            'moder_store_status'   => self::STATUS_NOACTIVE,
            'moder_manager'        => self::USER_SYSTEM_ID,
            'moder_manager_status' => self::STATUS_ACTIVE,
        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess($insert);

    }
}
