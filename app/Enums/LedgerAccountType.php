<?php

declare(strict_types=1);

namespace App\Enums;

enum LedgerAccountType: string
{
    case Asset = 'ASSET';
    case Liability = 'LIABILITY';
    case Income = 'INCOME';
    case Expense = 'EXPENSE';
    case Equity = 'EQUITY';
}
