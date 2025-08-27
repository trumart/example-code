<?php

namespace App\Services\Finance\RoleStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Services\Finance\RoleStrategy\RoleStrategyInterface;
use App\Services\Traits\CacheTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminFinanceStrategy implements RoleStrategyInterface
{
    use CacheTrait;

    private string $cacheTag = 'finance';

    public function getOperations(array $inp): Collection
    {

        $key = $this->makeCacheKey($inp);

        return Cache::tags([$this->cacheTag])
            ->remember($key, 600, fn () => (new Finance())->getOperations($inp));

    }
}
