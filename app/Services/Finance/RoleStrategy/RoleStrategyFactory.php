<?php



namespace App\Services\Finance\RoleStrategy;

use App\Services\Finance\RoleStrategy\Strategy\AdminFinanceStrategy;
use App\Services\Finance\RoleStrategy\Strategy\PurchaseFinanceStrategy;
use App\Services\Finance\RoleStrategy\Strategy\ShopFinanceStrategy;

class RoleStrategyFactory
{
    public static function make(): RoleStrategyInterface
    {

        $auth = auth()->user();

        return match ($auth->access) {
            'admin'                       => new AdminFinanceStrategy(),
            'purchase'                    => new PurchaseFinanceStrategy(),
            'shop', 'shopartner', 'sklad' => new ShopFinanceStrategy(),
            default                       => throw new \Exception('Роль ' . $auth->access . ' не поддерживается'),
        };

    }
}
