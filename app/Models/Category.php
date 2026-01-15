<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    #[Scope]
    protected function withBalance(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'balance' => LedgerEntry::query()
                    ->selectRaw('COALESCE(SUM(COALESCE(ledger_entries.amount_base, ledger_entries.amount)), 0)')
                    ->join('ledger_transactions', 'ledger_entries.transaction_id', '=', 'ledger_transactions.id')
                    ->join('ledger_accounts', 'ledger_entries.account_id', '=', 'ledger_accounts.id')
                    ->where(function (Builder|QueryBuilder $q): void {
                        $q->whereColumn('ledger_transactions.category_id', 'categories.id')
                            ->orWhereIn('ledger_transactions.category_id', function (Builder|QueryBuilder $subQuery): void {
                                $subQuery->select('id')
                                    ->from('categories as child_categories')
                                    ->whereColumn('child_categories.parent_id', 'categories.id');
                            });
                    })
                    ->whereColumn('ledger_accounts.type', 'categories.type'),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'parent_id' => 'integer',
            'budget_id' => 'integer',
            'name' => 'string',
            'type' => CategoryType::class,
            'is_archived' => 'boolean',
            'is_reportable' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
