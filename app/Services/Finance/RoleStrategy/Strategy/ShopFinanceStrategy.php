<?php



namespace App\Services\Finance\RoleStrategy\Strategy;

use App\Models\Finance\Finance;
use App\Services\Finance\RoleStrategy\RoleStrategyInterface;
use Illuminate\Support\Collection;

class ShopFinanceStrategy implements RoleStrategyInterface
{
    public function getOperations(array $inp): Collection
    {

        $inp['store_cash_id'] = auth()->user()->store;
        $inp['view']          = 1;

        $items = (new Finance())->getOperations($inp);

        return $items;

    }
}
