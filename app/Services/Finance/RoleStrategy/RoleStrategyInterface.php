<?php

namespace App\Services\Finance\RoleStrategy;

use Illuminate\Support\Collection;

interface RoleStrategyInterface
{
    public function getOperations(array $inp): Collection;
}
