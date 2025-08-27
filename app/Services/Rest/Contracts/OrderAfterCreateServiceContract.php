<?php

namespace App\Services\Rest\Contracts;

interface OrderAfterCreateServiceContract
{
    public function handle(array $inp): void;
}
