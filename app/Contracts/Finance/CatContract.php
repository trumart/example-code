<?php

namespace App\Contracts\Finance;

interface CatContract
{
    public function getCat(array $inp);

    public function getCats(array $inp = []);

    public function insert(array $inp);

    public function edit(array $inp);

    public function remove(int $id): bool;

    public function updateNum(array $inp);
}
