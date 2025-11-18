<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\LedgerEntryData;
use App\Data\LedgerTransactionData;
use App\Events\LedgerTransactionCreated;
use App\Exceptions\LedgerIntegrityException;
use App\Models\BudgetPeriod;
use App\Models\Category;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LedgerTransactionService
{
    public function create(User $user, LedgerTransactionData $transactionData): LedgerTransaction
    {
        $existingTransaction = $this->findTransactionByIdempotencyKey($user, $transactionData->idempotency_key);

        if ($existingTransaction !== null) {
            return $existingTransaction;
        }

        /** @var Collection<int, LedgerEntryData> $entryCollection */
        $entryCollection = collect($transactionData->entries->items());

        $this->assertEntryCount($entryCollection);
        $this->assertEntriesAreBalanced($entryCollection);

        $accounts = $this->loadAccounts($entryCollection);
        $categories = $this->loadCategories($entryCollection);

        $budgetPeriod = $this->determineBudgetPeriod($entryCollection, $categories, $transactionData->effective_at);

        $normalizedEntries = $entryCollection
            ->map(fn (LedgerEntryData $entry): array => $this->normalizeEntry($entry, $user, $accounts, $categories))
            ->all();

        try {
            $transaction = DB::transaction(function () use ($user, $transactionData, $normalizedEntries, $budgetPeriod): LedgerTransaction {
                $transaction = new LedgerTransaction();

                $transaction->forceFill(
                    $this->buildTransactionAttributes($transactionData, $user, $budgetPeriod),
                );

                $transaction->save();

                EloquentModel::unguarded(function () use ($transaction, $normalizedEntries): void {
                    $transaction->entries()->createMany($normalizedEntries);
                });

                return $transaction->fresh('entries.account');
            });
        } catch (QueryException $exception) {
            if (
                $transactionData->idempotency_key !== null
                && $this->isIdempotencyViolation($exception)
            ) {
                $existingTransaction = $this->findTransactionByIdempotencyKey($user, $transactionData->idempotency_key);

                if ($existingTransaction !== null) {
                    return $existingTransaction;
                }
            }

            throw $exception;
        }

        event(new LedgerTransactionCreated($transaction->load('budgetPeriod')));

        return $transaction;
    }

    /**
     * @param  Collection<int, LedgerEntryData>  $entries
     */
    private function assertEntryCount(Collection $entries): void
    {
        if ($entries->count() < 2) {
            throw LedgerIntegrityException::insufficientEntries();
        }
    }

    /**
     * @param  Collection<int, LedgerEntryData>  $entries
     */
    private function assertEntriesAreBalanced(Collection $entries): void
    {
        $total = $entries->reduce(
            static fn (string $carry, LedgerEntryData $entry): string => bcadd($carry, (string) $entry->amount, 6),
            '0'
        );

        if (bccomp($total, '0', 6) !== 0) {
            throw LedgerIntegrityException::unbalancedEntries();
        }
    }

    /**
     * @param  Collection<int, LedgerEntryData>  $entries
     * @return Collection<int, LedgerAccount>
     */
    private function loadAccounts(Collection $entries): Collection
    {
        $accountIds = $entries
            ->map(fn (LedgerEntryData $entry): int => $entry->account_id)
            ->unique();

        return LedgerAccount::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy(fn (LedgerAccount $account): int => $account->id);
    }

    /**
     * @param  Collection<int, LedgerEntryData>  $entries
     * @return Collection<int, Category>
     */
    private function loadCategories(Collection $entries): Collection
    {
        $categoryIds = $entries
            ->map(fn (LedgerEntryData $entry): ?int => $entry->category_id)
            ->filter()
            ->unique();

        return Category::query()
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy(fn (Category $category): int => $category->id);
    }

    /**
     * @param  Collection<int, LedgerAccount>  $accounts
     * @param  Collection<int, Category>  $categories
     * @return array<string, mixed>
     */
    private function normalizeEntry(
        LedgerEntryData $entry,
        User $user,
        Collection $accounts,
        Collection $categories
    ): array {
        $account = $this->resolveAccount($entry->account_id, $accounts);
        $this->assertAccountOwnership($account, $user);

        $amount = $this->normalizeAmount($entry->amount);
        $currencyCode = $this->determineCurrencyCode($entry, $account);

        $category = $this->resolveCategory($entry->category_id, $categories);
        $this->assertCategoryOwnership($category, $user);

        return [
            'account_id' => $account->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'amount_base' => $this->normalizeOptionalAmount($entry->amount_base),
            'category_id' => $category?->id,
            'memo' => $entry->memo,
        ];
    }

    private function determineBudgetPeriod(
        Collection $entries,
        Collection $categories,
        CarbonInterface $effectiveAt
    ): ?BudgetPeriod {
        $budgetIds = $this->resolveBudgetIds($entries, $categories);

        if ($budgetIds->isEmpty()) {
            return null;
        }

        if ($budgetIds->count() > 1) {
            throw LedgerIntegrityException::mixedBudgetAssignments();
        }

        /** @var int $budgetId */
        $budgetId = $budgetIds->first();

        $period = BudgetPeriod::query()
            ->where('budget_id', $budgetId)
            ->where('start_at', '<=', $effectiveAt->toDateString())
            ->where('end_at', '>', $effectiveAt->toDateString())
            ->orderByDesc('start_at')
            ->first();

        if ($period === null) {
            throw LedgerIntegrityException::budgetPeriodNotFound($budgetId, $effectiveAt->toDateString());
        }

        return $period;
    }

    /**
     * @param  Collection<int, LedgerEntryData>  $entries
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, int>
     */
    private function resolveBudgetIds(Collection $entries, Collection $categories): Collection
    {
        return $entries
            ->map(fn (LedgerEntryData $entry): ?int => $entry->category_id)
            ->filter()
            ->unique()
            ->map(function (int $categoryId) use ($categories): ?int {
                /** @var Category|null $category */
                $category = $categories->get($categoryId);

                if ($category === null) {
                    throw LedgerIntegrityException::categoryNotFound($categoryId);
                }

                return $category->budget_id;
            })
            ->filter(static fn (?int $budgetId): bool => $budgetId !== null)
            ->unique()
            ->values();
    }

    private function buildTransactionAttributes(
        LedgerTransactionData $transactionData,
        User $user,
        ?BudgetPeriod $budgetPeriod
    ): array {
        return [
            'description' => $transactionData->description,
            'effective_at' => $transactionData->effective_at,
            'posted_at' => $transactionData->posted_at,
            'reference' => $transactionData->reference,
            'source' => $transactionData->source,
            'idempotency_key' => $transactionData->idempotency_key,
            'user_id' => $user->id,
            'budget_period_id' => $budgetPeriod?->id,
        ];
    }

    /**
     * @param  Collection<int, LedgerAccount>  $accounts
     */
    private function resolveAccount(int $accountId, Collection $accounts): LedgerAccount
    {
        /** @var LedgerAccount|null $account */
        $account = $accounts->get($accountId);

        if ($account === null) {
            throw LedgerIntegrityException::accountNotFound($accountId);
        }

        return $account;
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function resolveCategory(?int $categoryId, Collection $categories): ?Category
    {
        if ($categoryId === null) {
            return null;
        }

        /** @var Category|null $category */
        $category = $categories->get($categoryId);

        if ($category === null) {
            throw LedgerIntegrityException::categoryNotFound($categoryId);
        }

        return $category;
    }

    private function normalizeAmount(int|float|string $amount): string
    {
        $normalized = (string) $amount;

        if (bccomp($normalized, '0', 6) === 0) {
            throw LedgerIntegrityException::amountMustBeNonZero();
        }

        return $normalized;
    }

    private function determineCurrencyCode(LedgerEntryData $entry, LedgerAccount $account): string
    {
        $currencyCode = $entry->currency_code ?? $account->currency_code;

        if ($currencyCode !== $account->currency_code) {
            throw LedgerIntegrityException::currencyMismatch();
        }

        return $currencyCode;
    }

    private function normalizeOptionalAmount(int|float|string|null $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return (string) $amount;
    }

    private function assertAccountOwnership(LedgerAccount $account, User $user): void
    {
        if ($account->user_id !== $user->id) {
            throw LedgerIntegrityException::accountOwnershipMismatch();
        }
    }

    private function assertCategoryOwnership(?Category $category, User $user): void
    {
        if ($category !== null && $category->user_id !== $user->id) {
            throw LedgerIntegrityException::categoryOwnershipMismatch();
        }
    }

    private function findTransactionByIdempotencyKey(User $user, ?string $idempotencyKey): ?LedgerTransaction
    {
        if ($idempotencyKey === null) {
            return null;
        }

        return LedgerTransaction::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->with('entries.account')
            ->first();
    }

    private function isIdempotencyViolation(QueryException $exception): bool
    {
        if ($exception->getCode() !== '23000') {
            return false;
        }

        return Str::contains($exception->getMessage(), 'ledger_transactions_user_id_idempotency_key_unique');
    }
}
