<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasCachedAggregates;
use App\Enums\CachedAggregateKey;
use App\Models\CachedAggregate;
use Database\Factories\BudgetPeriodFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BudgetPeriod extends Model
{
    use HasCachedAggregates;

    /** @use HasFactory<BudgetPeriodFactory> */
    use HasFactory;

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * @return HasMany<LedgerTransaction>
     */
    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class);
    }

    public function spentAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->resolveSpentAmount(),
        );
    }

    public function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => bcsub($this->amount, $this->resolveSpentAmount(), 6),
        );
    }

    public function usagePercent(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $amount = (float) $this->amount;

                if ($amount === 0.0) {
                    return '0';
                }

                $spent = (float) $this->resolveSpentAmount();

                return number_format(($spent / $amount) * 100, 2, '.', '');
            },
        );
    }

    public function rangeLabel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $start = $this->start_at?->format('Y-m-d');
                $endBoundary = $this->end_at?->copy();

                if ($endBoundary !== null) {
                    $endBoundary = $endBoundary->subDay();
                }

                $end = $endBoundary?->format('Y-m-d');

                if ($start === null && $end === null) {
                    return 'N/A';
                }

                if ($start === null) {
                    return $end ?? 'N/A';
                }

                if ($end === null) {
                    return "{$start} → ?";
                }

                return "{$start} → {$end}";
            },
        );
    }

    private function resolveSpentAmount(): string
    {
        $aggregate = $this->relationLoaded('aggregates')
            ? $this->aggregates
                ->first(static fn (CachedAggregate $aggregate): bool => $aggregate->key === CachedAggregateKey::Spent->value && $aggregate->scope === null)
            : $this->cachedAggregate(CachedAggregateKey::Spent);

        return bcadd((string) ($aggregate?->value_decimal ?? '0'), '0', 6);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_id' => 'integer',
            'start_at' => 'date',
            'end_at' => 'date',
            'amount' => 'decimal:6',
            'currency_code' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
