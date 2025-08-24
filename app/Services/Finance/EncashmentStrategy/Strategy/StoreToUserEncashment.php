<?php

namespace App\Services\Finance\EncashmentStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Models\Notice;
use App\Models\User;
use App\Services\Finance\EncashmentStrategy\EncashmentStrategyInterface;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;

class StoreToUserEncashment extends FinanceService implements EncashmentStrategyInterface
{
    /**
     * Инкассация (передача д/c) между магазинами
     *
     * @param array $data
     * @return JsonResponse
     */
    public function handle(array $data): JsonResponse
    {

        // Кому отдано
        $user = (new User())->getUser(['id' => $data['user']]);

        // Если система, то расчетный счет
        if ($user->id == self::USER_SYSTEM_ID) {
            $user->name = 'Расчетный счет';
        }

        // Сумма в кассе
        $finance = $this->getCashboxFinance([
            'store' => $data['store_cash'],
            'type'  => 'наличные',
            'date'  => $data['date'],
        ]);

        // Остаток д/c в кассе
        $balanceInCash = $finance['open']->close_sum_auto;

        if ($data['sum'] > $balanceInCash) {
            return response()->apiError('Сумма инкассации больше, чем остаток д/c в кассе');
        }

        // Если инкасация и 3100 меньше чем сумма текущая в кассе, то уведомляем руководство
        if (($data['sum'] + 3100) < $balanceInCash) {

            // Добавляем уведомление
            (new Notice())->add([
                'date'    => null,
                'user'    => 0,
                'store'   => 0,
                'access'  => 'admin',
                'title'   => 'Инкассация',
                'type'    => 'default',
                'message' => 'По магазину ' . $data['store_cash'] . ' инкассация добавлена на сумму ' . $data['sum'] . ', в кассе ' . $balanceInCash . ', а на утро ' . $finance['open']->open_sum . ' р.',
                'url'     => route('finances.index'),
            ]);

        }

        // Добавляем операцию Передача д/c - расход
        $insert = (new Finance())->insert([
            'store_id'             => $data['store_cash'],
            'store_cash_id'        => $data['store_cash'],
            'cashbox'              => 1,
            'type'                 => 'инкассация',
            'paycash'              => 1,
            'date'                 => $data['date'],
            'title'                => $user->name,
            'cat_id'               => 43, // Инкассация
            'sum'                  => $data['sum'],
            'user_id'              => auth()->user()->id,
            'user_pay_id'          => $data['user'],
            'view'                 => 1,
            'moder_store'          => $data['store_cash'],
            'moder_store_status'   => self::STATUS_ACTIVE,
            'moder_manager'        => null,
            'moder_manager_status' => null,
        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess($insert);

    }
}
