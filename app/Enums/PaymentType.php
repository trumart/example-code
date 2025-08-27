<?php

namespace App\Enums;

enum PaymentType: string
{
    case CASH = 'наличные';
    case CARD = 'банковской картой';
}
