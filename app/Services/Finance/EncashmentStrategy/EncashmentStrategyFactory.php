<?php

namespace App\Services\Finance\EncashmentStrategy;

use App\Services\Finance\EncashmentStrategy\Strategy\StoreToStoreEncashment;
use App\Services\Finance\EncashmentStrategy\Strategy\StoreToUserEncashment;
use App\Services\Finance\EncashmentStrategy\Strategy\UserToUserEncashment;

class EncashmentStrategyFactory
{
    /**
     * @throws \Exception
     */
    public static function make(string $type): EncashmentStrategyInterface
    {
        return match ($type) {
            'storeToStore' => new StoreToStoreEncashment(),
            'storeToUser'  => new StoreToUserEncashment(),
            'userToUser'   => new UserToUserEncashment(),
            default        => throw new \Exception('Тип ' . $type . ' не поддерживается'),
        };

    }
}
