<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasCachedAggregates;
use App\Enums\CachedAggregateKey;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

final class Budget extends Model
{
    use HasCachedAggregates;

    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    public function currentBalanceAggregate(): MorphOne
    {
        return $this->morphOne(CachedAggregate::class, 'aggregatable')
            ->where('key', CachedAggregateKey::CurrentBalance)
            ->whereNull('scope');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'name' => 'string',
            'period' => 'string',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
