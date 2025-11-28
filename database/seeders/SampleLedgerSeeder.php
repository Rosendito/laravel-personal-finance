<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CachedAggregateKey;
use App\Enums\CategoryType;
use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\BudgetPeriodSpentQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class SampleLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $usd = Currency::query()->firstOrCreate(
            ['code' => 'USD'],
            ['precision' => 2]
        );

        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        $checkingAccount = $this->createAccount($user, LedgerAccountType::ASSET, 'Checking Account', $usd->code);
        $creditCardAccount = $this->createAccount($user, LedgerAccountType::LIABILITY, 'Credit Card', $usd->code);
        $incomeAccount = $this->createAccount($user, LedgerAccountType::INCOME, 'Salary Income', $usd->code);
        $expenseAccount = $this->createAccount($user, LedgerAccountType::EXPENSE, 'Household Expenses', $usd->code);

        $salaryCategory = $this->createCategory($user, CategoryType::Income, 'Salary');
        $groceriesCategory = $this->createCategory($user, CategoryType::Expense, 'Groceries');

        $budget = Budget::factory()
            ->for($user)
            ->state([
                'name' => 'Monthly Budget',
                'is_active' => true,
            ])
            ->create();

        $currentStart = CarbonImmutable::now()->startOfMonth();

        $budgetPeriod = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt($currentStart, $currentStart->addMonth())
            ->state([
                'amount' => 600.00,
            ])
            ->create();

        $groceriesCategory->update(['budget_id' => $budget->id]);

        $salaryTransaction = $this->createTransaction($user, $checkingAccount, 'Monthly Salary', CarbonImmutable::now()->startOfMonth()->addDay(), null, $salaryCategory->id);
        $this->createEntry($salaryTransaction, $checkingAccount, amount: 5_000.00, currency: $usd->code);
        $this->createEntry($salaryTransaction, $incomeAccount, amount: -5_000.00, currency: $usd->code);

        $groceriesTransaction = $this->createTransaction(
            $user,
            $checkingAccount,
            'Grocery Run',
            CarbonImmutable::now()->startOfMonth()->addDays(3),
            $budgetPeriod,
            $groceriesCategory->id
        );
        $this->createEntry($groceriesTransaction, $expenseAccount, amount: 250.00, currency: $usd->code);
        $this->createEntry($groceriesTransaction, $checkingAccount, amount: -250.00, currency: $usd->code);

        $creditCardPayment = $this->createTransaction($user, $checkingAccount, 'Credit Card Payment', CarbonImmutable::now()->startOfMonth()->addDays(5));
        $this->createEntry($creditCardPayment, $creditCardAccount, amount: -400.00, currency: $usd->code);
        $this->createEntry($creditCardPayment, $checkingAccount, amount: 400.00, currency: $usd->code);

        $spent = app(BudgetPeriodSpentQueryService::class)->total($budgetPeriod->fresh());

        $budgetPeriod->upsertCachedAggregate(
            CachedAggregateKey::Spent,
            [
                'value_decimal' => $spent,
                'value_int' => null,
                'value_json' => null,
            ],
        );
    }

    private function createAccount(User $user, LedgerAccountType $type, string $name, string $currency): LedgerAccount
    {
        return LedgerAccount::factory()
            ->for($user)
            ->ofType($type)
            ->state([
                'name' => $name,
                'currency_code' => $currency,
            ])
            ->create();
    }

    private function createCategory(User $user, CategoryType $type, string $name): Category
    {
        $factory = Category::factory()
            ->state([
                'user_id' => $user->id,
                'name' => $name,
            ]);

        $factory = $type === CategoryType::Income ? $factory->income() : $factory->expense();

        return $factory->create();
    }

    private function createTransaction(
        User $user,
        LedgerAccount $account,
        string $description,
        CarbonImmutable $effectiveAt,
        ?BudgetPeriod $budgetPeriod = null,
        ?int $categoryId = null
    ): LedgerTransaction {
        return LedgerTransaction::factory()
            ->for($user)
            ->for($account, 'account')
            ->state([
                'description' => $description,
                'effective_at' => $effectiveAt,
                'posted_at' => $effectiveAt->toDateString(),
                'reference' => null,
                'source' => 'manual',
                'idempotency_key' => null,
                'budget_period_id' => $budgetPeriod?->id,
                'category_id' => $categoryId,
            ])
            ->create();
    }

    private function createEntry(
        LedgerTransaction $transaction,
        LedgerAccount $account,
        float $amount,
        string $currency
    ): LedgerEntry {
        return LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($account, 'account')
            ->state([
                'amount' => $amount,
                'currency_code' => $currency,
            ])
            ->create();
    }
}
