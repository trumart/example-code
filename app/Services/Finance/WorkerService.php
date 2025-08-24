<?php

namespace App\Services\Finance;

use App\Models\Finance\Finance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class WorkerService extends BaseService
{
    /**
     * Список выплат сотруднику из финансовых операций
     *
     * @param array $inp Массив с параметрами: userId, month, year.
     * @return Collection|JsonResponse Коллекция данных финансовых операций или ошибка
     */
    public function getWorkerPay(array $inp): Collection|JsonResponse
    {

        $now = Carbon::now();

        $targetDate = (!empty($inp['month']) && !empty($inp['year']))
            ? Carbon::createFromDate($inp['year'], $inp['month'], 1)
            : ($now->day < 16 ? $now->copy()->subMonth() : $now->copy());

        $inp['month'] ??= $targetDate->format('m');
        $inp['year']  ??= $targetDate->format('Y');

        $date = $inp['year'] . '-' . $inp['month'];

        $dateStart  = Carbon::parse($date)->startOfMonth()->format('Y-m-d');
        $dateFinish = Carbon::parse($date)->endOfMonth()->format('Y-m-d');

        if (
            !in_array(auth()->user()->access, ['admin', 'zam']) && $targetDate->lt($now->copy()->subMonth()->startOfMonth())
        ) {

            return response()->apiError('Не достаточно прав доступа');

        }

        $finances = (new Finance())->getOperations([
            'user_pay_id' => $inp['userId'],
            'date_start'  => $dateStart,
            'date_finish' => $dateFinish,
            'date_type'   => 'date',
        ]);

        return $finances;

    }
}
