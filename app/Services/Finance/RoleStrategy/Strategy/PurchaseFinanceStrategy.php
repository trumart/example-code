<?php



namespace App\Services\Finance\RoleStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Services\Finance\RoleStrategy\RoleStrategyInterface;
use Illuminate\Support\Collection;

class PurchaseFinanceStrategy implements RoleStrategyInterface
{
    public function getOperations(array $inp): Collection
    {

        $inp['cat_id'] = 32;

        $items = (new Finance())->getOperations($inp);

        return $items;

    }
}
