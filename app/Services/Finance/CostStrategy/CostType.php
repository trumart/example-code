<?php

namespace App\Services\Finance\CostStrategy;

enum CostType: string
{
    case CLEAR     = 'чистые';
    case TURNOVER  = 'оборотные';
    case OPERATION = 'операционные';
}
