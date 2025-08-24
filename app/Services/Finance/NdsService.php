<?php

namespace App\Services\Finance;

use App\Models\Finance\Finance;
use App\Models\Pay;
use App\Models\Upd;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NdsService extends BaseService
{
    /**
     * Сводный отчет по НДС
     *
     * @param array $inp Массив с параметрами: date_start, date_finish
     * @return array Массив с показателями
     */
    public function getReport(array $inp): array
    {

        $dateStart  = Carbon::parse($inp['date_start'])->format('Y-m-d');
        $dateFinish = Carbon::parse($inp['date_finish'])->format('Y-m-d');

        // Расходы с НДС
        $finance = (new Finance())->sumOperations([
            'type'        => 'расход',
            'paycash'     => 0,
            'nds'         => 1,
            'date_start'  => $dateStart,
            'date_finish' => $dateFinish,
            'date_type'   => 'date'
        ]);

        // Всего закуплено
        $purchase = (new Upd())->sumUpdNds([
            'setting'     => 1,
            'type'        => 'покупка',
            'date_start'  => $dateStart,
            'date_finish' => $dateFinish,
        ]);

        $pay = Pay::select(DB::raw('SUM(amount_deposit/100) AS sum'))
            ->where('created_at', '>=', $inp['date_start'] . ' 00:00:00')
            ->where('created_at', '<=', $inp['date_finish'] . ' 23:59:59')
            ->where('moder', 1)
            ->where('setting', 1)
            ->where(function ($query): void {
                $query
                    ->whereIn('type', ['банковской картой', 'онлайн', 'безнал', 'кредит'])
                    ->orWhere('amount_deposit', '<', 0)
                    ->orWhereNotNull('uuid');
            })
            ->first();

        // Продано УПД
        $upd = (new Upd())->sumUpdNds([
            'setting'     => 1,
            'type'        => 'продажа',
            'date_start'  => $dateStart,
            'date_finish' => $dateFinish,
        ]);

        $report = [
            'pay'          => (int) $pay->sum,
            'purchase'     => (int) $purchase,
            'upd'          => (int) $upd,
            'finance'      => (int) $finance,
            'nds_pay'      => $this->calcNds($pay->sum),
            'nds_purchase' => $this->calcNds($purchase),
            'nds_upd'      => $this->calcNds($upd),
            'nds_finance'  => $this->calcNds($finance),
        ];

        $report['nds'] = $report['nds_pay'] + $report['nds_upd'] - $report['nds_purchase'] - $report['nds_finance'];

        return $report;

    }

    // Расчет НДС
    private function calcNds($sum)
    {
        return round($sum * 20 / 120);
    }
}
