<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantType: string
{
    case STORE = 'store';
    case MARKETPLACE = 'marketplace';
    case OTHER = 'other';
}
