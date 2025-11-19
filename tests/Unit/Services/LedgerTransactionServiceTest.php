<?php

declare(strict_types=1);

use App\Data\LedgerTransactionData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;
use Illuminate\Support\Facades\Date;

describe(LedgerTransactionService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new LedgerTransactionService();

        // Use default currency (USDT) for base currency calculations
        $defaultCurrencyCode = config('finance.currency.default', 'USDT');
        $this->currency = Currency::where('code', $defaultCurrencyCode)->firstOrFail();

        $this->user = User::factory()->create();

        $this->assetAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Asset)
            ->state([
                'currency_code' => $this->currency->code,
                'name' => 'Cash',
            ])
            ->create();

        $this->incomeAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Income)
            ->state([
                'currency_code' => $this->currency->code,
                'name' => 'Salary',
            ])
            ->create();

        $this->makeTransactionData = function (array $transactionOverrides = [], ?array $entries = null): LedgerTransactionData {
            return LedgerTransactionData::from(
                array_merge(
                    [
                        'description' => 'Monthly Salary',
                        'effective_at' => Date::now(),
                        'posted_at' => Date::now(),
                        'reference' => 'PAY-001',
                        'source' => 'import',
                        'entries' => $entries ?? [
                            [
                                'account_id' => $this->assetAccount->id,
                                'amount' => 5_000,
                            ],
                            [
                                'account_id' => $this->incomeAccount->id,
                                'amount' => -5_000,
                            ],
                        ],
                    ],
                    $transactionOverrides,
                ),
            );
        };
    });

    it('creates balanced transactions with entries atomically', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $transaction = $this->service->create(
            $this->user,
            $makeTransactionData(),
        );

        expect($transaction->entries)->toHaveCount(2);
        expect($transaction->isBalanced())->toBeTrue();
    });

    it('requires at least two entries', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $this->assetAccount->id,
                'amount' => 50,
            ],
        ]);

        expect(fn (): mixed => $this->service->create(
            $this->user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'at least two');
    });

    it('requires balanced entries', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $this->assetAccount->id,
                'amount' => 100,
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'amount' => -50,
            ],
        ]);

        expect(fn (): mixed => $this->service->create(
            $this->user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'sum to zero');
    });

    it('validates account ownership independently from categories', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $foreignAccount = LedgerAccount::factory()
            ->for(User::factory()->create())
            ->ofType(LedgerAccountType::Expense)
            ->state([
                'currency_code' => $this->currency->code,
                'name' => 'Other',
            ])
            ->create();

        $category = Category::factory()
            ->expense()
            ->for($this->user)
            ->state([
                'name' => 'Housing',
            ])
            ->create();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $foreignAccount->id,
                'amount' => 200,
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'amount' => -200,
                'category_id' => $category->id,
            ],
        ]);

        expect(fn (): mixed => $this->service->create(
            $this->user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'same user');
    });

    it('persists metadata, memo, and categories when valid', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $category = Category::factory()
            ->expense()
            ->for($this->user)
            ->state([
                'name' => 'Housing',
            ])
            ->create();

        $data = $makeTransactionData(
            transactionOverrides: [
                'reference' => 'PAY-999',
                'source' => 'import',
                'idempotency_key' => 'txn-pay-999',
                'posted_at' => Date::now()->addDay(),
            ],
            entries: [
                [
                    'account_id' => $this->assetAccount->id,
                    'amount' => -1_500,
                    'memo' => 'Rent payment',
                ],
                [
                    'account_id' => $this->incomeAccount->id,
                    'amount' => 1_500,
                    'memo' => 'Rent offset',
                    'category_id' => $category->id,
                ],
            ],
        );

        $transaction = $this->service->create(
            $this->user,
            $data,
        );

        expect($transaction->reference)->toBe('PAY-999');
        expect($transaction->idempotency_key)->toBe('txn-pay-999');
        expect($transaction->entries->first()->memo)->toBe('Rent payment');
        expect($transaction->entries->last()->memo)->toBe('Rent offset');
        expect($transaction->entries->firstWhere('category_id', $category->id))->not->toBeNull();
    });

    it('snapshots the budget assigned to referenced categories', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $budget = Budget::factory()
            ->for($this->user)
            ->state(['name' => 'Food'])
            ->create();

        $periodStart = Date::now()->startOfMonth();

        $period = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt($periodStart, $periodStart->copy()->addMonth())
            ->create();

        $category = Category::factory()
            ->expense()
            ->for($this->user)
            ->state([
                'name' => 'Dining',
                'budget_id' => $budget->id,
            ])
            ->create();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $this->assetAccount->id,
                'amount' => -1_000,
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'amount' => 1_000,
                'category_id' => $category->id,
            ],
        ]);

        $transaction = $this->service->create(
            $this->user,
            $data,
        );

        expect($transaction->budget_period_id)->toBe($period->id);
    });

    it('rejects transactions that mix categories from different budgets', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $foodBudget = Budget::factory()->for($this->user)->state(['name' => 'Food'])->create();
        $rentBudget = Budget::factory()->for($this->user)->state(['name' => 'Rent'])->create();

        $foodCategory = Category::factory()
            ->expense()
            ->for($this->user)
            ->state(['budget_id' => $foodBudget->id])
            ->create();

        $rentCategory = Category::factory()
            ->expense()
            ->for($this->user)
            ->state(['budget_id' => $rentBudget->id])
            ->create();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $this->assetAccount->id,
                'amount' => -700,
                'category_id' => $foodCategory->id,
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'amount' => 700,
                'category_id' => $rentCategory->id,
            ],
        ]);

        expect(fn (): mixed => $this->service->create(
            $this->user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'multiple budgets');
    });

    it('calculates amount_base values when exchange rate is provided', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $eur = Currency::factory()->create(['code' => 'EUR']);

        $eurAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'EUR'])
            ->create();

        $data = $makeTransactionData(
            transactionOverrides: [
                'currency_code' => 'EUR',
                'exchange_rate' => '0.92', // 1 USDT = 0.92 EUR
            ],
            entries: [
                [
                    'account_id' => $eurAccount->id,
                    'amount' => 92, // 92 EUR
                    'currency_code' => 'EUR',
                ],
                [
                    'account_id' => $this->incomeAccount->id,
                    'amount' => -100, // -100 USDT (base currency)
                ],
            ]
        );

        $transaction = $this->service->create(
            $this->user,
            $data,
        );

        expect($transaction->entries)->toHaveCount(2);

        $eurEntry = $transaction->entries->firstWhere('account_id', $eurAccount->id);
        $baseEntry = $transaction->entries->firstWhere('account_id', $this->incomeAccount->id);

        // 92 EUR / 0.92 = 100 USDT base
        expect($eurEntry->amount_base)->toBe('100.000000');
        // Base currency entry is already in base currency
        expect($baseEntry->amount_base)->toBe('-100.000000');
    });

    it('rejects categories that belong to another user', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $foreignCategory = Category::factory()
            ->expense()
            ->for(User::factory()->create())
            ->create();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $this->assetAccount->id,
                'amount' => 200,
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'amount' => -200,
                'category_id' => $foreignCategory->id,
            ],
        ]);

        expect(fn (): mixed => $this->service->create(
            $this->user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'category does not belong');
    });

    it('rejects currency mismatches per entry', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        Currency::factory()
            ->state([
                'code' => 'EUR',
            ])
            ->create();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $this->assetAccount->id,
                'amount' => 300,
                'currency_code' => 'EUR',
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'amount' => -300,
            ],
        ]);

        expect(fn (): mixed => $this->service->create(
            $this->user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'currency');
    });

    it('returns the existing transaction when an idempotency key is reused', function (): void {
        /** @var callable $makeTransactionData */
        $makeTransactionData = $this->makeTransactionData;

        $data = $makeTransactionData(transactionOverrides: [
            'idempotency_key' => 'dup-123',
        ]);

        $first = $this->service->create(
            $this->user,
            $data,
        );

        $second = $this->service->create(
            $this->user,
            $data,
        );

        expect($first->id)->toBe($second->id);
        expect($second->entries)->toHaveCount(2);
        expect(LedgerTransaction::query()->count())->toBe(1);
    });
});
