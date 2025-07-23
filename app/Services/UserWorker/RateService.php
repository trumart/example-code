<?php

namespace App\Services\UserWorker;

use App\Models\Order;
use App\Models\ReportStat;
use App\Models\Tasks;
use App\Models\UserWorker;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RateService extends Service
{
    /**
     * Рейтинг сотрудников
     *
     * @param array $inp
     * @return array|JsonResponse
     */
    public function getWorkerRate(string $date): array|JsonResponse
    {

        $dateStart  = Carbon::parse($date)->startOfMonth()->toDateTimeString();
        $dateFinish = Carbon::parse($date)->endOfMonth()->toDateTimeString();

        $users = (new UserWorker())->getUsersRate([
            'date_start'  => $dateStart,
            'date_finish' => $dateFinish,
        ]);

        foreach ($users as $k => $user) {

            $arrStore = [];

            $user->num = $k;
            $user->num++;

            // Если работает в нескольких точках
            !empty($user->walking_store) ? $arrStore = explode(',', $user->walking_store) : $arrStore[] = $user->store;

            // Создано заказов
            $user->created = (new Order())->sumOrders([
                'user'        => $user->id,
                'status'      => ['Подтвержден', 'В обработке', 'В пути', 'Поступил', 'Закрыт'],
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
                'date_type'   => 'created_at',
                'moder'       => 1
            ]);

            // Подтверждено заказов
            $user->accepted = (new Order())->sumOrders([
                'moder_user'  => $user->id,
                'status'      => ['Подтвержден', 'В обработке', 'В пути', 'Поступил', 'Закрыт'],
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
                'date_type'   => 'created_at'
            ]);

            // Отменено заказов
            $user->cancel = (new Order())->sumOrders([
                'moder_user'  => $user->id,
                'nouser'      => self::USER_SYSTEM_ID,
                'status'      => 'Отменен',
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
                'date_type'   => 'created_at',
                'moder'       => 1,
            ]);

            // Кол-во выполненных задач
            $user->tasks = (new Tasks())->count([
                'accepted'      => 1,
                'accepted_user' => $user->id,
                'date_start'    => $dateStart,
                'date_finish'   => $dateFinish,
                'date_type'     => 'created_at'
            ]);

            // Кол-во заказов созданных
            $user->count_order_created = (new Order())->count(null, [
                'moder'       => 1,
                'user'        => $user->id,
                'store'       => $user->store,
                'nostatus'    => 'Отменен',
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
                'date_type'   => 'created_at'
            ]);

            // Кол-во заказов
            $user->count_order = (new Order())->count(null, [
                'moder'       => 1,
                'store'       => $user->store,
                'nostatus'    => 'Отменен',
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
                'date_type'   => 'created_at'
            ]);

            // Время до первого звонка
            $user->time_ring = (new ReportStat())->avgStat($user, [
                'title'       => 'Время 1-го звонка в раб. время',
                'user'        => $user->id,
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
            ]);

            // Время до первого звонка
            $user->time_accept = (new ReportStat())->avgStat($user, [
                'title'       => 'Время до подтверждения',
                'user'        => $user->id,
                'date_start'  => $dateStart,
                'date_finish' => $dateFinish,
            ]);

            // Доля отмен
            if ($user->cancel > 0 && $user->sum > 0) {

                $user->share_cancel        = ($user->cancel / $user->sum) * 100;
                $user->share_cancelPrepare = number_format($user->share_cancel, 1, '.', ' ');

            }

            // Таймы
            if (!empty($user->time_ring->val)) {
                $user->time_ring = round($user->time_ring->val / 60, 0);
            } // минуты
            else {
                $user->time_ring = '';
            }

            if (!empty($user->time_accept->val)) {
                $user->time_accept = round($user->time_accept->val / 60 / 60, 2);
            } // часы
            else {
                $user->time_accept = '';
            }

            // Конверсия
            @$user->conversion = (int)(($user->count_order_created / $user->count_order) * 100);

            // Обработка
            $user->sumPrepare     = number_format($user->sum, 0, ',', ' ');
            $user->creditPrepare  = number_format($user->credit, 0, ',', ' ');
            $user->beznalPrepare  = number_format($user->beznal, 0, ',', ' ');
            $user->cancelPrepare  = number_format($user->cancel, 0, ',', ' ');
            $user->createdPrepare = number_format($user->created, 0, ',', ' ');

        }

        return apiSuccess($users);

    }
}
