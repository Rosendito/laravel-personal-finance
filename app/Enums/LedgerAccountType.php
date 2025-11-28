<?php

declare(strict_types=1);

namespace App\Enums;

enum LedgerAccountType: string
{
    case ASSET = 'ASSET';
    case LIABILITY = 'LIABILITY';
    case INCOME = 'INCOME';
    case EXPENSE = 'EXPENSE';
    case EQUITY = 'EQUITY';

    /**
     * Return valid subtypes for this type.
     */
    public function subtypes(): array
    {
        return match ($this) {
            self::ASSET => [
                LedgerAccountSubType::CASH,
                LedgerAccountSubType::BANK,
                LedgerAccountSubType::WALLET,

                LedgerAccountSubType::LOAN_RECEIVABLE,
                LedgerAccountSubType::INVESTMENT,
            ],

            self::LIABILITY => [
                LedgerAccountSubType::LOAN_PAYABLE,
                LedgerAccountSubType::CREDIT_CARD,
            ],

            // INCOME/EXPENSE/EQUITY do not use account-level subtypes
            self::INCOME,
            self::EXPENSE,
            self::EQUITY => [],
        };
    }
}
