<?php

namespace App\Contracts\Finance;

interface FinanceContract
{
    public function getOperation(array $inp);

    public function getOperations(array $inp = []);

    public function insert(array $inp);

    public function edit(array $inp);

    public function remove(int $id): bool;
}
