<?php

namespace App\Services\Finance\RoleStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Services\Finance\BaseService;
use App\Services\Finance\RoleStrategy\RoleStrategyInterface;
use App\Services\Traits\CacheTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PurchaseFinanceStrategy extends BaseService implements RoleStrategyInterface
{
    use CacheTrait;

    private string $cacheTag = 'finance';

    public function getOperations(array $inp): Collection
    {

        // Отображение только банковских операций
        $inp['cat_id'] = self::CAT_BANK_OPERATION;

        $key = $this->makeCacheKey($inp);

        return Cache::tags([$this->cacheTag])
            ->remember($key, 600, fn () => (new Finance())->getOperations($inp));

    }
}
