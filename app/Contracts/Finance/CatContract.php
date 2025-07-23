<?php



namespace App\Contracts\Finance;

interface CatContract
{
    public function getCat(array $inp);

    public function getCats();

    public function insert(array $inp);

    public function edit(array $inp);

    public function remove(array $inp);

    public function updateNum(array $inp);
}
