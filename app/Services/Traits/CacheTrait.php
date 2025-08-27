<?php

namespace App\Services\Traits;

trait CacheTrait
{
    public function makeCacheKey(array $inp): string
    {

        // нормализуем массив, чтобы порядок ключей не влиял на md5
        ksort($inp);

        // формируем ключ (читаемая часть + md5)
        return md5(json_encode($inp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    }
}
