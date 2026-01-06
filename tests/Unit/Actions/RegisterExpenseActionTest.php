<?php

declare(strict_types=1);

use App\Actions\RegisterExpenseAction;
use App\Data\Transactions\RegisterExpenseData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\Category;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Date;

describe(RegisterExpenseAction::class, function (): void {
    /**
     * @return array{
     *   action: RegisterExpenseAction,
     *   user: User,
     *   paymentAccount: LedgerAccount,
     *   expenseCategory: Category
     * }
     */
    $makeContext = function (): array {
        $action = resolve(RegisterExpenseAction::class);
        $user = User::factory()->create();

        $paymentAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'USD'])
            ->create();

        $expenseCategory = Category::factory()
            ->expense()
            ->for($user)
            ->create();

        return [
            'action' => $action,
            'user' => $user,
            'paymentAccount' => $paymentAccount,
            'expenseCategory' => $expenseCategory,
        ];
    };

    it('creates a transaction debiting the fundamental expense account', function () use ($makeContext): void {
        [
            'action' => $action,
            'user' => $user,
            'paymentAccount' => $paymentAccount,
            'expenseCategory' => $expenseCategory,
        ] = $makeContext();

        // Fund the account first
        LedgerEntry::factory()
            ->for($paymentAccount, 'account')
            ->for(LedgerTransaction::factory()->create(['user_id' => $user->id]), 'transaction')
            ->create(['amount' => 1000, 'currency_code' => 'USD']);

        $data = RegisterExpenseData::from([
            'description' => 'Grocery shopping',
            'effective_at' => Date::now(),
            'posted_at' => Date::now(),
            'account_id' => $paymentAccount->id,
            'amount' => '275.45',
            'memo' => 'Weekly groceries',
            'reference' => 'EXP-2024-10',
            'source' => 'manual',
            'idempotency_key' => 'expense-grocery-1',
            'category_id' => $expenseCategory->id,
        ]);

        $transaction = $action->execute($user, $data);

        $expenseAccount = LedgerAccount::query()
            ->where('user_id', $user->id)
            ->where('currency_code', 'USD')
            ->where('type', LedgerAccountType::EXPENSE)
            ->where('is_fundamental', true)
            ->firstOrFail();

        $paymentEntry = $transaction->entries
            ->firstWhere('account_id', $paymentAccount->id);

        $expenseEntry = $transaction->entries
            ->firstWhere('account_id', $expenseAccount->id);

        expect($transaction->description)->toBe('Grocery shopping')
            ->and($transaction->category_id)->toBe($expenseCategory->id)
            ->and($paymentEntry->amount)->toBe('-275.450000')
            ->and($paymentEntry->memo)->toBe('Weekly groceries')
            ->and($expenseEntry->amount)->toBe('275.450000');
    });

    it('rejects accounts that do not belong to the user', function () use ($makeContext): void {
        [
            'action' => $action,
            'user' => $user,
        ] = $makeContext();

        $foreignAccount = LedgerAccount::factory()
            ->for(User::factory()->create())
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'USD'])
            ->create();

        $data = RegisterExpenseData::from([
            'description' => 'Office supplies',
            'effective_at' => Date::now(),
            'account_id' => $foreignAccount->id,
            'amount' => '45',
        ]);

        expect(fn (): mixed => $action->execute($user, $data))
            ->toThrow(LedgerIntegrityException::class);
    });
});
