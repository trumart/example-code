<?php

namespace App\Contracts\Finance;

interface SettingContract
{
    public function getSetting(array $inp);

    public function getSettings(array $inp = []);

    public function insert(array $inp);

    public function edit(array $inp);

    public function remove(int $id): bool;
}
