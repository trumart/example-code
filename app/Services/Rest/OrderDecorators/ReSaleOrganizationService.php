<?php

namespace App\Services\Rest\OrderDecorators;

use App\Services\Rest\Contracts\OrderAfterCreateServiceContract;
use App\Services\Service;
use App\Services\Upd\ReSaleService;

/**
 * Перепродажа товара между организациями
 */
class ReSaleOrganizationService extends Service implements OrderAfterCreateServiceContract
{
    public function handle(array $inp): void
    {

        (new ReSaleService())->reSaleOrderItems($inp['code']);

    }
}
