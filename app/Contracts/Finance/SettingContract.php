<?php



namespace App\Contracts\Finance;

interface SettingContract
{
    public function getSetting(array $inp);

    public function getSettings();

    public function insert(array $inp);

    public function remove(array $inp);
}
