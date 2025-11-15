<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case Income = 'INCOME';
    case Expense = 'EXPENSE';
}
