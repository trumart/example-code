<?php

namespace App\Services\Finance;

use App\Services\Service;

class BaseService extends Service
{
    // Категория банковских операций
    public const int CAT_BANK_OPERATION = 32;

    // С какого числа применять автоматическое распределение
    public const string DATE_START_DISTRIBUTION = '2025-01-01';
}
