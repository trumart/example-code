<?php

namespace App\Contracts\Route;

interface LoaderContract
{
    public function getLoader(array $inp);

    public function getLoaders();

    public function insert(array $inp);

    public function remove(array $inp);
}
