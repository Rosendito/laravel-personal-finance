<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;

describe('LedgerAccount Deletion', function (): void {
    it('prevents deletion of fundamental accounts', function (): void {
        $user = User::factory()->create();

        // Get an existing fundamental account (created by InitializeUserSpace)
        $fundamentalAccount = LedgerAccount::where('user_id', $user->id)
            ->where('is_fundamental', true)
            ->first();

        expect($fundamentalAccount)->not->toBeNull()
            ->and($fundamentalAccount->is_fundamental)->toBeTrue()
            ->and(fn () => $fundamentalAccount->delete())
            ->toThrow(LedgerIntegrityException::class, 'Cannot delete fundamental accounts');
    });

    it('prevents deletion of accounts with entries', function (): void {
        $user = User::factory()->create();

        $account = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'USD'])
            ->create();

        // Create a transaction with entries
        $transaction = LedgerTransaction::factory()
            ->for($user)
            ->create();

        LedgerEntry::factory()
            ->state([
                'transaction_id' => $transaction->id,
                'account_id' => $account->id,
            ])
            ->create();

        expect(fn () => $account->delete())
            ->toThrow(LedgerIntegrityException::class, 'Cannot delete account that has ledger entries');
    });

    it('allows deletion of accounts without entries', function (): void {
        $user = User::factory()->create();

        $account = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'USD'])
            ->create();

        expect($account->delete())->toBeTrue()
            ->and(LedgerAccount::find($account->id))->toBeNull();
    });

    it('prevents deletion of accounts marked as fundamental', function (): void {
        $user = User::factory()->create();

        $account = LedgerAccount::factory()
            ->for($user)
            ->state([
                'name' => 'External Expenses (EUR)',
                'type' => LedgerAccountType::Expense,
                'currency_code' => 'USD',
                'is_fundamental' => true,
            ])
            ->create();

        expect($account->is_fundamental)->toBeTrue()
            ->and(fn () => $account->delete())
            ->toThrow(LedgerIntegrityException::class, 'Cannot delete fundamental accounts');
    });
});
