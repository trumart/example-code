<?php

namespace App\Services\Finance\CostStrategy;

use App\Services\Finance\CostStrategy\Strategy\ClearCostStrategy;
use App\Services\Finance\CostStrategy\Strategy\OperationCostStrategy;
use App\Services\Finance\CostStrategy\Strategy\TurnoverCostStrategy;

class CostStrategyFactory
{
    public static function make(CostType $type): CostStrategyInterface
    {
        return match ($type) {
            CostType::CLEAR     => new ClearCostStrategy(),
            CostType::TURNOVER  => new TurnoverCostStrategy(),
            CostType::OPERATION => new OperationCostStrategy(),
        };
    }
}
