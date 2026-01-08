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
    /**
     * @return array{
     *   service: LedgerTransactionService,
     *   currency: Currency,
     *   user: User,
     *   assetAccount: LedgerAccount,
     *   incomeAccount: LedgerAccount,
     *   makeTransactionData: Closure(array=, ?array=): LedgerTransactionData
     * }
     */
    $makeContext = function (): array {
        $service = new LedgerTransactionService();

        $defaultCurrencyCode = config('finance.currency.default', 'USDT');
        $currency = Currency::query()->where('code', $defaultCurrencyCode)->firstOrFail();

        $user = User::factory()->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Cash',
            ])
            ->create();

        $incomeAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::INCOME)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Salary',
            ])
            ->create();

        $makeTransactionData = fn (array $transactionOverrides = [], ?array $entries = null): LedgerTransactionData => LedgerTransactionData::from(
            array_merge(
                [
                    'description' => 'Monthly Salary',
                    'effective_at' => Date::now(),
                    'posted_at' => Date::now(),
                    'reference' => 'PAY-001',
                    'source' => 'import',
                    'entries' => $entries ?? [
                        [
                            'account_id' => $assetAccount->id,
                            'amount' => 5_000,
                        ],
                        [
                            'account_id' => $incomeAccount->id,
                            'amount' => -5_000,
                        ],
                    ],
                ],
                $transactionOverrides,
            ),
        );

        return [
            'service' => $service,
            'currency' => $currency,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ];
    };

    it('creates balanced transactions with entries atomically', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $transaction = $service->create(
            $user,
            $makeTransactionData(),
        );

        expect($transaction->entries)->toHaveCount(2);
        expect($transaction->isBalanced())->toBeTrue();
    });

    it('requires at least two entries', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $assetAccount->id,
                'amount' => 50,
            ],
        ]);

        expect(fn (): mixed => $service->create(
            $user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'at least two');
    });

    it('requires balanced entries', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $assetAccount->id,
                'amount' => 100,
            ],
            [
                'account_id' => $incomeAccount->id,
                'amount' => -50,
            ],
        ]);

        expect(fn (): mixed => $service->create(
            $user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'sum to zero');
    });

    it('validates account ownership independently from categories', function () use ($makeContext): void {
        [
            'service' => $service,
            'currency' => $currency,
            'user' => $user,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $foreignAccount = LedgerAccount::factory()
            ->for(User::factory()->create())
            ->ofType(LedgerAccountType::EXPENSE)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Other',
            ])
            ->create();

        $category = Category::factory()
            ->expense()
            ->for($user)
            ->state([
                'name' => 'Housing',
            ])
            ->create();

        $data = $makeTransactionData(
            transactionOverrides: [
                'category_id' => $category->id,
            ],
            entries: [
                [
                    'account_id' => $foreignAccount->id,
                    'amount' => 200,
                ],
                [
                    'account_id' => $incomeAccount->id,
                    'amount' => -200,
                ],
            ],
        );

        expect(fn (): mixed => $service->create(
            $user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'same user');
    });

    it('persists metadata, memo, and categories when valid', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $category = Category::factory()
            ->expense()
            ->for($user)
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
                'category_id' => $category->id,
            ],
            entries: [
                [
                    'account_id' => $assetAccount->id,
                    'amount' => -1_500,
                    'memo' => 'Rent payment',
                ],
                [
                    'account_id' => $incomeAccount->id,
                    'amount' => 1_500,
                    'memo' => 'Rent offset',
                ],
            ],
        );

        $transaction = $service->create(
            $user,
            $data,
        );

        expect($transaction->reference)->toBe('PAY-999');
        expect($transaction->idempotency_key)->toBe('txn-pay-999');
        expect($transaction->category_id)->toBe($category->id);
        expect($transaction->entries->first()->memo)->toBe('Rent payment');
        expect($transaction->entries->last()->memo)->toBe('Rent offset');
    });

    it('snapshots the budget assigned to referenced categories', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $budget = Budget::factory()
            ->for($user)
            ->state(['name' => 'Food'])
            ->create();

        $periodStart = Date::now()->startOfMonth();

        $period = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt($periodStart, $periodStart->copy()->addMonth())
            ->create();

        $category = Category::factory()
            ->expense()
            ->for($user)
            ->state([
                'name' => 'Dining',
                'budget_id' => $budget->id,
            ])
            ->create();

        $data = $makeTransactionData(
            transactionOverrides: [
                'category_id' => $category->id,
            ],
            entries: [
                [
                    'account_id' => $assetAccount->id,
                    'amount' => -1_000,
                ],
                [
                    'account_id' => $incomeAccount->id,
                    'amount' => 1_000,
                ],
            ],
        );

        $transaction = $service->create(
            $user,
            $data,
        );

        expect($transaction->budget_period_id)->toBe($period->id);
    });

    it('assigns budget period based on transaction category', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $foodBudget = Budget::factory()->for($user)->state(['name' => 'Food'])->create();
        $periodStart = Date::now()->startOfMonth();

        $period = BudgetPeriod::factory()
            ->for($foodBudget)
            ->startingAt($periodStart, $periodStart->copy()->addMonth())
            ->create();

        $foodCategory = Category::factory()
            ->expense()
            ->for($user)
            ->state(['budget_id' => $foodBudget->id])
            ->create();

        $data = $makeTransactionData(
            transactionOverrides: [
                'category_id' => $foodCategory->id,
            ],
            entries: [
                [
                    'account_id' => $assetAccount->id,
                    'amount' => -700,
                ],
                [
                    'account_id' => $incomeAccount->id,
                    'amount' => 700,
                ],
            ],
        );

        $transaction = $service->create(
            $user,
            $data,
        );

        expect($transaction->budget_period_id)->toBe($period->id);
    });

    it('calculates amount_base values when exchange rate is provided', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $eur = Currency::query()->updateOrCreate(['code' => 'EUR'], ['precision' => 2]);

        $eurAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
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
                    'account_id' => $incomeAccount->id,
                    'amount' => -100, // -100 USDT (base currency)
                ],
            ]
        );

        $transaction = $service->create(
            $user,
            $data,
        );

        expect($transaction->entries)->toHaveCount(2);

        $eurEntry = $transaction->entries->firstWhere('account_id', $eurAccount->id);
        $baseEntry = $transaction->entries->firstWhere('account_id', $incomeAccount->id);

        // 92 EUR / 0.92 = 100 USDT base
        expect($eurEntry->amount_base)->toBe('100.000000');
        // Base currency entry is already in base currency
        expect($baseEntry->amount_base)->toBe('-100.000000');
    });

    it('rejects categories that belong to another user', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $foreignCategory = Category::factory()
            ->expense()
            ->for(User::factory()->create())
            ->create();

        $data = $makeTransactionData(
            transactionOverrides: [
                'category_id' => $foreignCategory->id,
            ],
            entries: [
                [
                    'account_id' => $assetAccount->id,
                    'amount' => 200,
                ],
                [
                    'account_id' => $incomeAccount->id,
                    'amount' => -200,
                ],
            ],
        );

        expect(fn (): mixed => $service->create(
            $user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'category does not belong');
    });

    it('rejects currency mismatches per entry', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'assetAccount' => $assetAccount,
            'incomeAccount' => $incomeAccount,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        Currency::query()->updateOrCreate(['code' => 'EUR'], ['precision' => 2]);

        $data = $makeTransactionData(entries: [
            [
                'account_id' => $assetAccount->id,
                'amount' => 300,
                'currency_code' => 'EUR',
            ],
            [
                'account_id' => $incomeAccount->id,
                'amount' => -300,
            ],
        ]);

        expect(fn (): mixed => $service->create(
            $user,
            $data,
        ))->toThrow(LedgerIntegrityException::class, 'currency');
    });

    it('returns the existing transaction when an idempotency key is reused', function () use ($makeContext): void {
        [
            'service' => $service,
            'user' => $user,
            'makeTransactionData' => $makeTransactionData,
        ] = $makeContext();

        $data = $makeTransactionData(transactionOverrides: [
            'idempotency_key' => 'dup-123',
        ]);

        $first = $service->create(
            $user,
            $data,
        );

        $second = $service->create(
            $user,
            $data,
        );

        expect($first->id)->toBe($second->id);
        expect($second->entries)->toHaveCount(2);
        expect(LedgerTransaction::query()->count())->toBe(1);
    });
});
