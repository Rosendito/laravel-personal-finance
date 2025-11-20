<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\LedgerIntegrityException;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    protected static function booted(): void
    {
        self::saving(function (self $entry): void {
            $entry->assertNonZeroAmount();

            $account = $entry->resolveAccount();
            $transaction = $entry->resolveTransaction();

            $entry->alignCurrencyWithAccount($account);
            $entry->assertAccountBelongsToTransactionUser($account, $transaction);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_id' => 'integer',
            'account_id' => 'integer',
            'amount' => 'decimal:6',
            'amount_base' => 'decimal:6',
            'currency_code' => 'string',
            'memo' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    private function assertNonZeroAmount(): void
    {
        $amount = $this->amount;

        if ($amount === null || bccomp((string) $amount, '0', 6) === 0) {
            throw LedgerIntegrityException::amountMustBeNonZero();
        }
    }

    private function resolveAccount(): LedgerAccount
    {
        if ($this->relationLoaded('account')) {
            /** @var LedgerAccount $account */
            $account = $this->getRelation('account');

            return $account;
        }

        $account = LedgerAccount::query()->find($this->account_id);

        if ($account === null) {
            throw LedgerIntegrityException::accountNotFound((int) $this->account_id);
        }

        return $account;
    }

    private function resolveTransaction(): LedgerTransaction
    {
        if ($this->relationLoaded('transaction')) {
            /** @var LedgerTransaction $transaction */
            $transaction = $this->getRelation('transaction');

            return $transaction;
        }

        $transaction = LedgerTransaction::query()->find($this->transaction_id);

        if ($transaction === null) {
            throw LedgerIntegrityException::transactionNotFound((int) $this->transaction_id);
        }

        return $transaction;
    }

    private function alignCurrencyWithAccount(LedgerAccount $account): void
    {
        if ($this->currency_code === null) {
            $this->currency_code = $account->currency_code;

            return;
        }

        if ($this->currency_code !== $account->currency_code) {
            throw LedgerIntegrityException::currencyMismatch();
        }
    }

    private function assertAccountBelongsToTransactionUser(LedgerAccount $account, LedgerTransaction $transaction): void
    {
        if ($account->user_id !== $transaction->user_id) {
            throw LedgerIntegrityException::accountOwnershipMismatch();
        }
    }
}
