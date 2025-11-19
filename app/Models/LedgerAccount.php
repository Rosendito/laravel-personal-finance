<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LedgerAccountType;
use Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LedgerAccount extends Model
{
    /** @use HasFactory<LedgerAccountFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    public function scopeWithMostTransactions(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'transactions_count' => LedgerEntry::selectRaw('count(distinct transaction_id)')
                    ->whereColumn('account_id', 'ledger_accounts.id'),
            ])
            ->orderByDesc('transactions_count');
    }

    public function scopeWithMostIncomeTransactions(Builder $query): Builder
    {
        return self::withMostTransactionsByAccountType($query, LedgerAccountType::Income, 'income_transactions_count');
    }

    public function scopeWithMostExpenseTransactions(Builder $query): Builder
    {
        return self::withMostTransactionsByAccountType($query, LedgerAccountType::Expense, 'expense_transactions_count');
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'balance' => LedgerEntry::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('account_id', 'ledger_accounts.id'),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'name' => 'string',
            'type' => LedgerAccountType::class,
            'currency_code' => 'string',
            'is_archived' => 'boolean',
            'is_fundamental' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    private static function withMostTransactionsByAccountType(Builder $query, LedgerAccountType $accountType, string $countColumn): Builder
    {
        return $query
            ->addSelect([
                $countColumn => LedgerEntry::selectRaw('count(distinct ledger_entries.transaction_id)')
                    ->from('ledger_entries')
                    ->whereColumn('ledger_entries.account_id', 'ledger_accounts.id')
                    ->whereExists(static function ($subQuery) use ($accountType): void {
                        $subQuery->selectRaw('1')
                            ->from('ledger_entries as other_entries')
                            ->join('ledger_accounts as other_accounts', 'other_entries.account_id', '=', 'other_accounts.id')
                            ->whereColumn('other_entries.transaction_id', 'ledger_entries.transaction_id')
                            ->where('other_accounts.type', $accountType);
                    }),
            ])
            ->orderByDesc($countColumn);
    }
}
