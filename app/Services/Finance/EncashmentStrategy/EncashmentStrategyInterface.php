<?php

namespace App\Services\Finance\EncashmentStrategy;

interface EncashmentStrategyInterface
{
    public function handle(array $data): mixed;
}
