<?php

namespace App\Services\Finance\CostStrategy\Strategy;

use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Services\Finance\CostStrategy\CostStrategyInterface;

class ClearCostStrategy implements CostStrategyInterface
{
    /**
     * Сумма чистых расходов исключая категории не учитывающиеся в расходах
     *
     * @param array $inp Массив с параметрами: date_type, date_start, date_finish
     * @return float Сумма расходов
     */
    public function sumCost(array $inp): float
    {

        // Категория которые не учитываются в отчетах
        $arrCats = (new Cat())->getCats([
            'noconsider' => true,
        ])->pluck('id')->toArray();

        // Сумма расходов
        return (new Finance())->sumOperations([
            'type'        => 'расход',
            'no_cat_id'   => $arrCats,
            'date_start'  => $inp['date_start'],
            'date_finish' => $inp['date_finish'],
            'date_type'   => $inp['date_type'] ?? 'date',
        ]);

    }
}
