<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LedgerTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(
            Category::class,
            LedgerEntry::class,
            'transaction_id',
            'id',
            'id',
            'category_id',
        );
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
