<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LedgerTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LedgerTransaction extends Model
{
    /** @use HasFactory<LedgerTransactionFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'transaction_id');
    }

    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get categories as a collection for compatibility with existing code.
     * Returns a collection with the category if it exists, empty collection otherwise.
     *
     * @return Collection<int, Category>
     */
    public function getCategoriesAttribute(): Collection
    {
        if ($this->category_id === null) {
            return new Collection();
        }

        return new Collection([$this->category]);
    }

    public function scopeWithAmountSummary(Builder $query): Builder
    {
        return $query->addSelect([
            'amount_summary' => LedgerEntry::selectRaw('MAX(ABS(amount))')
                ->whereColumn('transaction_id', 'ledger_transactions.id'),
            'amount_currency' => LedgerEntry::select('currency_code')
                ->whereColumn('transaction_id', 'ledger_transactions.id')
                ->orderByRaw('ABS(amount) DESC')
                ->limit(1),
        ]);
    }

    public function scopeWithBaseAmountSummary(Builder $query): Builder
    {
        return $query->addSelect([
            'amount_base_summary' => LedgerEntry::selectRaw('MAX(ABS(COALESCE(amount_base, amount)))')
                ->whereColumn('transaction_id', 'ledger_transactions.id'),
        ]);
    }

    /**
     * Apply a category filter with an exclusive "uncategorized" option.
     *
     * @param  array<int, int|string>  $categoryIds
     */
    public function scopeWhereCategoryFilter(Builder $query, bool $uncategorized, array $categoryIds = []): Builder
    {
        if ($uncategorized) {
            return $query->whereNull('category_id');
        }

        $categoryIds = array_values(array_filter($categoryIds, static fn (mixed $value): bool => $value !== null && $value !== ''));

        if ($categoryIds === []) {
            return $query;
        }

        $categoryIds = array_values(array_map(static fn (mixed $value): int => (int) $value, $categoryIds));

        return $query->whereIn('category_id', $categoryIds);
    }

    public function isBalanced(): bool
    {
        $entries = $this->entries()->get();

        if ($entries->count() < 2) {
            return false;
        }

        $total = $entries->reduce(
            static fn (string $carry, LedgerEntry $entry): string => bcadd($carry, (string) ($entry->amount_base ?? $entry->amount), 6),
            '0'
        );

        return bccomp($total, '0', 6) === 0;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'budget_period_id' => 'integer',
            'category_id' => 'integer',
            'description' => 'string',
            'effective_at' => 'datetime',
            'posted_at' => 'date',
            'reference' => 'string',
            'source' => 'string',
            'idempotency_key' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
