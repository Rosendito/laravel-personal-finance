<?php

declare(strict_types=1);

use App\Actions\RegisterExpenseAction;
use App\Data\Transactions\RegisterExpenseData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\Category;
use App\Models\LedgerAccount;
use App\Models\User;
use Illuminate\Support\Facades\Date;

describe(RegisterExpenseAction::class, function (): void {
    beforeEach(function (): void {
        $this->action = app(RegisterExpenseAction::class);
        $this->user = User::factory()->create();

        $this->paymentAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'USD'])
            ->create();

        $this->expenseCategory = Category::factory()
            ->expense()
            ->for($this->user)
            ->create();
    });

    it('creates a transaction debiting the fundamental expense account', function (): void {
        // Fund the account first
        App\Models\LedgerEntry::factory()
            ->for($this->paymentAccount, 'account')
            ->for(App\Models\LedgerTransaction::factory()->create(['user_id' => $this->user->id]), 'transaction')
            ->create(['amount' => 1000, 'currency_code' => 'USD']);

        /** @var RegisterExpenseAction $action */
        $action = $this->action;

        $data = RegisterExpenseData::from([
            'description' => 'Grocery shopping',
            'effective_at' => Date::now(),
            'posted_at' => Date::now(),
            'account_id' => $this->paymentAccount->id,
            'amount' => '275.45',
            'memo' => 'Weekly groceries',
            'reference' => 'EXP-2024-10',
            'source' => 'manual',
            'idempotency_key' => 'expense-grocery-1',
            'category_id' => $this->expenseCategory->id,
        ]);

        $transaction = $action->execute($this->user, $data);

        $expenseAccount = LedgerAccount::query()
            ->where('user_id', $this->user->id)
            ->where('currency_code', 'USD')
            ->where('type', LedgerAccountType::Expense)
            ->where('is_fundamental', true)
            ->firstOrFail();

        $paymentEntry = $transaction->entries
            ->firstWhere('account_id', $this->paymentAccount->id);

        $expenseEntry = $transaction->entries
            ->firstWhere('account_id', $expenseAccount->id);

        expect($transaction->description)->toBe('Grocery shopping')
            ->and($paymentEntry->amount)->toBe('-275.450000')
            ->and($paymentEntry->memo)->toBe('Weekly groceries')
            ->and($expenseEntry->amount)->toBe('275.450000')
            ->and($expenseEntry->category_id)->toBe($this->expenseCategory->id);
    });

    it('rejects accounts that do not belong to the user', function (): void {
        /** @var RegisterExpenseAction $action */
        $action = $this->action;

        $foreignAccount = LedgerAccount::factory()
            ->for(User::factory()->create())
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'USD'])
            ->create();

        $data = RegisterExpenseData::from([
            'description' => 'Office supplies',
            'effective_at' => Date::now(),
            'account_id' => $foreignAccount->id,
            'amount' => '45',
        ]);

        expect(fn (): mixed => $action->execute($this->user, $data))
            ->toThrow(LedgerIntegrityException::class);
    });
});
