<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\LedgerAccountType;
use RuntimeException;

final class LedgerIntegrityException extends RuntimeException
{
    public static function amountMustBeNonZero(): self
    {
        return new self('Ledger entry amount must be non-zero.');
    }

    public static function accountNotFound(int $accountId): self
    {
        return new self("Ledger account [{$accountId}] was not found.");
    }

    public static function accountReferenceRequired(): self
    {
        return new self('Ledger entries must reference an account.');
    }

    public static function transactionNotFound(int $transactionId): self
    {
        return new self("Ledger transaction [{$transactionId}] was not found.");
    }

    public static function accountOwnershipMismatch(): self
    {
        return new self('Ledger account does not belong to the same user as the transaction.');
    }

    public static function categoryOwnershipMismatch(): self
    {
        return new self('Ledger category does not belong to the same user as the transaction.');
    }

    public static function categoryNotFound(int $categoryId): self
    {
        return new self("Ledger category [{$categoryId}] was not found.");
    }

    public static function currencyMismatch(): self
    {
        return new self('Ledger entry currency must match the related account currency.');
    }

    public static function insufficientEntries(): self
    {
        return new self('Ledger transactions require at least two entries.');
    }

    public static function unbalancedEntries(): self
    {
        return new self('Ledger transactions must have entries that sum to zero.');
    }

    public static function mixedBudgetAssignments(): self
    {
        return new self('Ledger transactions cannot be linked to multiple budgets.');
    }

    public static function budgetPeriodNotFound(int $budgetId, string $effectiveDate): self
    {
        return new self("Budget [{$budgetId}] does not have a period covering {$effectiveDate}.");
    }

    public static function cannotDeleteFundamentalAccount(): self
    {
        return new self('Cannot delete fundamental accounts (External Expenses/Income).');
    }

    public static function cannotDeleteAccountWithEntries(): self
    {
        return new self('Cannot delete account that has ledger entries associated.');
    }

    public static function fundamentalAccountNotFound(
        int $userId,
        string $currencyCode,
        LedgerAccountType $type
    ): self {
        return new self(sprintf(
            'Fundamental %s account for user %d and currency %s was not found.',
            $type->name,
            $userId,
            $currencyCode,
        ));
    }

    public static function insufficientFunds(): self
    {
        return new self('Insufficient funds in the account.');
    }
}
