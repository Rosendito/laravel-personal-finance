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

        $accounts = $this->loadAccounts($entryCollection);
        $category = $this->loadCategory($transactionData);

        if ($category !== null) {
            $this->assertCategoryOwnership($category, $user);
        }

        $baseAmounts = $this->calculateBaseAmounts($entryCollection, $accounts, $transactionData);

        $this->assertEntriesAreBalanced($baseAmounts);

        $budgetPeriod = $this->determineBudgetPeriod($category, $transactionData->effective_at);

        $normalizedEntries = $entryCollection
            ->map(fn (LedgerEntryData $entry, int $index): array => $this->normalizeEntry(
                $entry,
                $baseAmounts[$index],
                $user,
                $accounts
            ))
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
     * @param  array<int, string>  $baseAmounts
     */
    private function assertEntriesAreBalanced(array $baseAmounts): void
    {
        $total = '0';
        foreach ($baseAmounts as $amount) {
            $total = bcadd($total, $amount, 6);
        }

        // Allow small rounding difference for base currency conversion (e.g. 0.01)
        $absTotal = str_starts_with($total, '-') ? mb_substr($total, 1) : $total;

        if (bccomp($absTotal, '0.01', 6) === 1) {
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

    private function loadCategory(LedgerTransactionData $transactionData): ?Category
    {
        if ($transactionData->category_id === null) {
            return null;
        }

        $category = Category::query()->find($transactionData->category_id);

        if ($category === null) {
            throw LedgerIntegrityException::categoryNotFound($transactionData->category_id);
        }

        return $category;
    }

    /**
     * @param  Collection<int, LedgerEntryData>  $entries
     * @param  Collection<int, LedgerAccount>  $accounts
     * @return array<int, string>
     */
    private function calculateBaseAmounts(
        Collection $entries,
        Collection $accounts,
        LedgerTransactionData $transactionData
    ): array {
        $defaultCurrency = config('finance.currency.default');
        $baseAmounts = [];
        $knownBaseTotal = '0';
        $unknownIndices = [];
        $unknownTotalAmount = '0';

        foreach ($entries as $index => $entry) {
            $account = $this->resolveAccount($entry->account_id, $accounts);
            $currencyCode = $this->determineCurrencyCode($entry, $account);
            $amount = (string) $entry->amount;

            if ($currencyCode === $defaultCurrency) {
                $baseAmounts[$index] = $amount;
                $knownBaseTotal = bcadd($knownBaseTotal, $amount, 6);
            } elseif (
                $transactionData->exchange_rate !== null
                && $transactionData->currency_code === $currencyCode
            ) {
                // amount_base = amount / exchange_rate
                // We use bcdiv with high precision
                $rate = (string) $transactionData->exchange_rate;
                $baseAmount = bcdiv($amount, $rate, 6);
                $baseAmounts[$index] = $baseAmount;
                $knownBaseTotal = bcadd($knownBaseTotal, $baseAmount, 6);
            } else {
                $unknownIndices[] = $index;
                // We track the absolute amount for distribution ratio?
                // No, we should track the signed amount to distribute properly?
                // Actually, usually unknown entries balance the known entries.
                // If known total is -100, unknown total base must be +100.
                // We distribute +100 among unknown entries based on their face value weights.
                // But wait, if we have multiple unknown entries with different signs?
                // That would be complex. Assuming unknown entries are on the "other side" of the transaction.
                // Let's sum the absolute values of unknown entries to find weights.
                $unknownTotalAmount = bcadd($unknownTotalAmount, (string) abs((float) $amount), 6);
            }
        }

        if (empty($unknownIndices)) {
            return $baseAmounts;
        }

        // The remaining base amount needed to balance the transaction to 0
        $remainingBase = bcmul($knownBaseTotal, '-1', 6);

        // Distribute remainingBase among unknown entries
        $distributedTotal = '0';
        $lastUnknownIndex = end($unknownIndices);

        foreach ($unknownIndices as $index) {
            if ($index === $lastUnknownIndex) {
                // Allocate the rest to the last one to avoid rounding errors
                $baseAmounts[$index] = bcsub($remainingBase, $distributedTotal, 6);
            } else {
                $entry = $entries[$index];
                $amount = (string) abs((float) $entry->amount);

                if (bccomp($unknownTotalAmount, '0', 6) === 0) {
                    // Should not happen if there are entries, unless amounts are 0
                    $share = '0';
                } else {
                    // share = remainingBase * (amount / unknownTotalAmount)
                    $ratio = bcdiv($amount, $unknownTotalAmount, 10);
                    $share = bcmul($remainingBase, $ratio, 6);
                }

                $baseAmounts[$index] = $share;
                $distributedTotal = bcadd($distributedTotal, $share, 6);
            }
        }

        return $baseAmounts;
    }

    /**
     * @param  Collection<int, LedgerAccount>  $accounts
     * @return array<string, mixed>
     */
    private function normalizeEntry(
        LedgerEntryData $entry,
        string $amountBase,
        User $user,
        Collection $accounts
    ): array {
        $account = $this->resolveAccount($entry->account_id, $accounts);
        $this->assertAccountOwnership($account, $user);

        $amount = $this->normalizeAmount($entry->amount);
        $currencyCode = $this->determineCurrencyCode($entry, $account);

        return [
            'account_id' => $account->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'amount_base' => $amountBase,
            'memo' => $entry->memo,
        ];
    }

    private function determineBudgetPeriod(
        ?Category $category,
        CarbonInterface $effectiveAt
    ): ?BudgetPeriod {
        if ($category === null || $category->budget_id === null) {
            return null;
        }

        $period = BudgetPeriod::query()
            ->where('budget_id', $category->budget_id)
            ->where('start_at', '<=', $effectiveAt->toDateString())
            ->where('end_at', '>', $effectiveAt->toDateString())
            ->orderByDesc('start_at')
            ->first();

        if ($period === null) {
            throw LedgerIntegrityException::budgetPeriodNotFound($category->budget_id, $effectiveAt->toDateString());
        }

        return $period;
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
            'category_id' => $transactionData->category_id,
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
