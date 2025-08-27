<?php

namespace App\Services\Rest\OrderDecorators;

use App\Models\OrderDelivery;
use App\Services\Rest\Contracts\OrderAfterCreateServiceContract;
use App\Services\Service;
use Carbon\Carbon;

class InstallationOrderService extends Service implements OrderAfterCreateServiceContract
{
    public function handle(array $inp): void
    {

        $auth = auth()->user();

        // Если товар был собран
        if (empty($inp['installation_price'])) {
            return;
        }

        // Добавляем сборку в заказ
        (new OrderDelivery())->insertInstallation($auth, [
            'code'               => $inp['code'],
            'store'              => $inp['store'],
            'installation_price' => $inp['installation_price'],
            'installation_user'  => self::USER_SYSTEM_ID,
            'installation_date'  => Carbon::parse($inp['installation_date'])->format('Y-m-d'),
            'text'               => 'сборка из зала',
        ]);

        // Ставим исполнение сборки
        (new OrderDelivery())->closeInstallationSystem($auth, [
            'code'              => $inp['code'],
            'installation_user' => self::USER_SYSTEM_ID,
        ]);

    }
}
