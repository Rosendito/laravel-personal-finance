<?php

declare(strict_types=1);

use App\Actions\RegisterIncomeAction;
use App\Data\Transactions\RegisterIncomeData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\Category;
use App\Models\LedgerAccount;
use App\Models\User;
use Illuminate\Support\Facades\Date;

describe(RegisterIncomeAction::class, function (): void {
    beforeEach(function (): void {
        $this->action = resolve(RegisterIncomeAction::class);
        $this->user = User::factory()->create();

        $this->depositAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'USD'])
            ->create();

        $this->incomeCategory = Category::factory()
            ->income()
            ->for($this->user)
            ->create();
    });

    it('creates a transaction crediting the fundamental income account', function (): void {
        /** @var RegisterIncomeAction $action */
        $action = $this->action;

        $data = RegisterIncomeData::from([
            'description' => 'Salary payment',
            'effective_at' => Date::now(),
            'posted_at' => Date::now(),
            'account_id' => $this->depositAccount->id,
            'amount' => '1500',
            'memo' => 'Company payroll',
            'reference' => 'PAY-2024-09',
            'source' => 'manual',
            'idempotency_key' => 'income-salary-1',
            'category_id' => $this->incomeCategory->id,
        ]);

        $transaction = $action->execute($this->user, $data);

        $incomeAccount = LedgerAccount::query()
            ->where('user_id', $this->user->id)
            ->where('currency_code', 'USD')
            ->where('type', LedgerAccountType::INCOME)
            ->where('is_fundamental', true)
            ->firstOrFail();

        $depositEntry = $transaction->entries
            ->firstWhere('account_id', $this->depositAccount->id);

        $incomeEntry = $transaction->entries
            ->firstWhere('account_id', $incomeAccount->id);

        expect($transaction->description)->toBe('Salary payment')
            ->and($transaction->reference)->toBe('PAY-2024-09')
            ->and($transaction->category_id)->toBe($this->incomeCategory->id)
            ->and($depositEntry->amount)->toBe('1500.000000')
            ->and($depositEntry->memo)->toBe('Company payroll')
            ->and($incomeEntry->amount)->toBe('-1500.000000');
    });

    it('rejects accounts that do not belong to the user', function (): void {
        /** @var RegisterIncomeAction $action */
        $action = $this->action;

        $foreignAccount = LedgerAccount::factory()
            ->for(User::factory()->create())
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'USD'])
            ->create();

        $data = RegisterIncomeData::from([
            'description' => 'Bonus payment',
            'effective_at' => Date::now(),
            'account_id' => $foreignAccount->id,
            'amount' => '200',
        ]);

        expect(fn (): mixed => $action->execute($this->user, $data))
            ->toThrow(LedgerIntegrityException::class);
    });
});
