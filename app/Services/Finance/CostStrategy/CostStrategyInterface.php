<?php

namespace App\Services\Finance\CostStrategy;

interface CostStrategyInterface
{
    public function sumCost(array $inp): float;
}
