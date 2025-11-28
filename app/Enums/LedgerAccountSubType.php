<?php

declare(strict_types=1);

namespace App\Enums;

enum LedgerAccountSubType: string
{
    // ASSET -> assets that are liquid (money that you can use directly)
    case CASH = 'CASH';       // efectivo
    case BANK = 'BANK';       // bank accounts
    case WALLET = 'WALLET';   // digital wallets

    // ASSET -> assets that are not liquid (you can't use them directly)
    case LOAN_RECEIVABLE = 'LOAN_RECEIVABLE'; // money that you are owed
    case INVESTMENT = 'INVESTMENT';           // investments

    // LIABILITY -> debts that you owe
    case LOAN_PAYABLE = 'LOAN_PAYABLE'; // loans that you owe
    case CREDIT_CARD = 'CREDIT_CARD';   // credit cards

    public static function liquidSubtypes(): array
    {
        return [
            self::CASH,
            self::BANK,
            self::WALLET,
        ];
    }

    /**
     * Identify which LedgerAccountType this subtype belongs to.
     */
    public function type(): LedgerAccountType
    {
        return match ($this) {
            self::CASH,
            self::BANK,
            self::WALLET,
            self::LOAN_RECEIVABLE,
            self::INVESTMENT => LedgerAccountType::ASSET,

            self::LOAN_PAYABLE,
            self::CREDIT_CARD => LedgerAccountType::LIABILITY,
        };
    }

    /**
     * Helper: Liquid or not.
     */
    public function isLiquid(): bool
    {
        return in_array($this, self::liquidSubtypes());
    }
}
