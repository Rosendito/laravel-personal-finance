<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\User;

describe(LedgerAccount::class, function (): void {
    it('automatically creates fundamental accounts for new currencies', function (): void {
        // 1. Setup: Ensure USD (default) exists (handled by global setup mostly, but consistent here)
        // Currency::factory()->create(['code' => 'USD']); // REMOVED to avoid dupes with global setup

        Currency::factory()->create(['code' => 'EUR']);

        // 2. Create User -> Should trigger InitializeUserSpace -> Creates USD accounts
        $user = User::factory()->create();

        // Check default accounts exist
        expect(LedgerAccount::query()->where('user_id', $user->id)->where('currency_code', 'USD')->count())
            ->toBe(2) // Expense + Income
            ->and(LedgerAccount::query()->where('user_id', $user->id)->where('currency_code', 'EUR')->count())
            ->toBe(0); // No EUR yet

        // 3. Action: Create a new Asset Account in EUR
        LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'EUR'])
            ->create();

        // 4. Assert: Fundamental EUR accounts should have been created automatically by the Observer
        $eurExpenses = LedgerAccount::query()->where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->where('type', LedgerAccountType::EXPENSE)
            ->first();

        $eurIncome = LedgerAccount::query()->where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->where('type', LedgerAccountType::INCOME)
            ->first();

        expect($eurExpenses)->not->toBeNull()
            ->and($eurExpenses->name)->toBe('External Expenses (EUR)')
            ->and($eurIncome)->not->toBeNull()
            ->and($eurIncome->name)->toBe('External Income (EUR)');
    });

    it('does not duplicate fundamental accounts if they already exist', function (): void {
        // Currency::factory()->create(['code' => 'USD']); // Handled by global setup
        $user = User::factory()->create();

        // USD accounts created on user creation.
        $countBefore = LedgerAccount::query()->where('user_id', $user->id)->count();

        // Create an asset account in USD -> Should trigger observer, but find existing accounts
        LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'USD'])
            ->create();

        // Count should increase by exactly 1 (the new asset account), not 3 (re-creating fundamentals)
        expect(LedgerAccount::query()->where('user_id', $user->id)->count())->toBe($countBefore + 1);
    });

    it('respects configured default currency', function (): void {
        // Configurar USDT como default
        config(['finance.currency.default' => 'USDT']);

        // Ensure USDT exists (may already exist from migration)
        Currency::query()->firstOrCreate(
            ['code' => 'USDT'],
            ['precision' => 2]
        );

        $user = User::factory()->create();

        // Verificar que se crearon cuentas USDT
        expect(LedgerAccount::query()->where('user_id', $user->id)->where('currency_code', 'USDT')->count())
            ->toBe(2);
    });
})->skip();
