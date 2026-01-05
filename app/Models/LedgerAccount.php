<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LedgerAccountSubType;
use App\Enums\LedgerAccountType;
use Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
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

    #[Scope]
    protected function withMostTransactions(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'transactions_count' => LedgerEntry::query()->selectRaw('count(distinct transaction_id)')
                    ->whereColumn('account_id', 'ledger_accounts.id'),
            ])
            ->orderByDesc('transactions_count');
    }

    #[Scope]
    protected function withMostIncomeTransactions(Builder $query): Builder
    {
        return $this->withMostTransactionsByAccountType($query, LedgerAccountType::INCOME, 'income_transactions_count');
    }

    #[Scope]
    protected function withMostExpenseTransactions(Builder $query): Builder
    {
        return $this->withMostTransactionsByAccountType($query, LedgerAccountType::EXPENSE, 'expense_transactions_count');
    }

    #[Scope]
    protected function withBalance(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'balance' => LedgerEntry::query()->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('account_id', 'ledger_accounts.id'),
            ]);
    }

    #[Scope]
    protected function withBaseBalance(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'balance_base' => LedgerEntry::query()->selectRaw('COALESCE(SUM(COALESCE(amount_base, amount)), 0)')
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
            'subtype' => LedgerAccountSubType::class,
            'currency_code' => 'string',
            'is_archived' => 'boolean',
            'is_fundamental' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    private function withMostTransactionsByAccountType(Builder $query, LedgerAccountType $accountType, string $countColumn): Builder
    {
        return $query
            ->addSelect([
                $countColumn => LedgerEntry::query()->selectRaw('count(distinct ledger_entries.transaction_id)')
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
