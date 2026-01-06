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
    /**
     * @return array{
     *   action: RegisterIncomeAction,
     *   user: User,
     *   depositAccount: LedgerAccount,
     *   incomeCategory: Category
     * }
     */
    $makeContext = function (): array {
        $action = resolve(RegisterIncomeAction::class);
        $user = User::factory()->create();

        $depositAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => 'USD'])
            ->create();

        $incomeCategory = Category::factory()
            ->income()
            ->for($user)
            ->create();

        return [
            'action' => $action,
            'user' => $user,
            'depositAccount' => $depositAccount,
            'incomeCategory' => $incomeCategory,
        ];
    };

    it('creates a transaction crediting the fundamental income account', function () use ($makeContext): void {
        [
            'action' => $action,
            'user' => $user,
            'depositAccount' => $depositAccount,
            'incomeCategory' => $incomeCategory,
        ] = $makeContext();

        $data = RegisterIncomeData::from([
            'description' => 'Salary payment',
            'effective_at' => Date::now(),
            'posted_at' => Date::now(),
            'account_id' => $depositAccount->id,
            'amount' => '1500',
            'memo' => 'Company payroll',
            'reference' => 'PAY-2024-09',
            'source' => 'manual',
            'idempotency_key' => 'income-salary-1',
            'category_id' => $incomeCategory->id,
        ]);

        $transaction = $action->execute($user, $data);

        $incomeAccount = LedgerAccount::query()
            ->where('user_id', $user->id)
            ->where('currency_code', 'USD')
            ->where('type', LedgerAccountType::INCOME)
            ->where('is_fundamental', true)
            ->firstOrFail();

        $depositEntry = $transaction->entries
            ->firstWhere('account_id', $depositAccount->id);

        $incomeEntry = $transaction->entries
            ->firstWhere('account_id', $incomeAccount->id);

        expect($transaction->description)->toBe('Salary payment')
            ->and($transaction->reference)->toBe('PAY-2024-09')
            ->and($transaction->category_id)->toBe($incomeCategory->id)
            ->and($depositEntry->amount)->toBe('1500.000000')
            ->and($depositEntry->memo)->toBe('Company payroll')
            ->and($incomeEntry->amount)->toBe('-1500.000000');
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

        $data = RegisterIncomeData::from([
            'description' => 'Bonus payment',
            'effective_at' => Date::now(),
            'account_id' => $foreignAccount->id,
            'amount' => '200',
        ]);

        expect(fn (): mixed => $action->execute($user, $data))
            ->toThrow(LedgerIntegrityException::class);
    });
});
