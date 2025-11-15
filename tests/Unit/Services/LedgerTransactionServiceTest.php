<?php

declare(strict_types=1);

use App\Data\LedgerTransactionData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\LedgerTransactionService;
use Illuminate\Support\Facades\Date;

describe(LedgerTransactionService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new LedgerTransactionService();
        $this->user = User::factory()->create();

        $this->currency = Currency::factory()
            ->state([
                'code' => 'USD',
                'precision' => 2,
            ])
            ->create();

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
});
