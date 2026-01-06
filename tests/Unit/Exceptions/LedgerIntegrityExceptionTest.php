<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;

describe(LedgerIntegrityException::class, function (): void {
    it('builds expected exception messages', function (callable $factory, string $expectedMessage): void {
        $exception = $factory();

        expect($exception)->toBeInstanceOf(LedgerIntegrityException::class);
        expect($exception->getMessage())->toBe($expectedMessage);
    })->with([
        'amountMustBeNonZero' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::amountMustBeNonZero(),
            'Ledger entry amount must be non-zero.',
        ],
        'accountNotFound' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::accountNotFound(123),
            'Ledger account [123] was not found.',
        ],
        'accountReferenceRequired' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::accountReferenceRequired(),
            'Ledger entries must reference an account.',
        ],
        'transactionNotFound' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::transactionNotFound(456),
            'Ledger transaction [456] was not found.',
        ],
        'accountOwnershipMismatch' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::accountOwnershipMismatch(),
            'Ledger account does not belong to the same user as the transaction.',
        ],
        'categoryOwnershipMismatch' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::categoryOwnershipMismatch(),
            'Ledger category does not belong to the same user as the transaction.',
        ],
        'categoryNotFound' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::categoryNotFound(789),
            'Ledger category [789] was not found.',
        ],
        'currencyMismatch' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::currencyMismatch(),
            'Ledger entry currency must match the related account currency.',
        ],
        'insufficientEntries' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::insufficientEntries(),
            'Ledger transactions require at least two entries.',
        ],
        'unbalancedEntries' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::unbalancedEntries(),
            'Ledger transactions must have entries that sum to zero.',
        ],
        'mixedBudgetAssignments' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::mixedBudgetAssignments(),
            'Ledger transactions cannot be linked to multiple budgets.',
        ],
        'budgetPeriodNotFound' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::budgetPeriodNotFound(10, '2025-01-01'),
            'Budget [10] does not have a period covering 2025-01-01.',
        ],
        'cannotDeleteFundamentalAccount' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::cannotDeleteFundamentalAccount(),
            'Cannot delete fundamental accounts (External Expenses/Income).',
        ],
        'cannotDeleteAccountWithEntries' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::cannotDeleteAccountWithEntries(),
            'Cannot delete account that has ledger entries associated.',
        ],
        'fundamentalAccountNotFound' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::fundamentalAccountNotFound(
                userId: 7,
                currencyCode: 'USD',
                type: LedgerAccountType::EXPENSE,
            ),
            'Fundamental EXPENSE account for user 7 and currency USD was not found.',
        ],
        'insufficientFunds' => [
            fn (): LedgerIntegrityException => LedgerIntegrityException::insufficientFunds(),
            'Insufficient funds in the account.',
        ],
    ]);
});
