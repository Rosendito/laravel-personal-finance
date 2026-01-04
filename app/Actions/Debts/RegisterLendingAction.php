<?php

declare(strict_types=1);

namespace App\Actions\Debts;

use App\Data\LedgerTransactionData;
use App\Data\Transactions\RegisterDebtData;
use App\Enums\LedgerAccountSubType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;

final class RegisterLendingAction
{
    public function __construct(
        private readonly LedgerTransactionService $ledgerTransactionService,
    ) {}

    public function execute(User $user, RegisterDebtData $data): LedgerTransaction
    {
        $loanReceivableAccount = $this->findAccountForUser($user, $data->target_account_id);
        $liquidAccount = $this->findAccountForUser($user, $data->contra_account_id);

        $this->validateLoanReceivableAccount($loanReceivableAccount);
        $this->validateLiquidAccount($liquidAccount);
        $this->validateCurrencyMatch($loanReceivableAccount, $liquidAccount);

        $amount = $this->formatAmount($data->amount);

        $transactionData = LedgerTransactionData::from([
            'description' => $data->description,
            'effective_at' => $data->effective_at,
            'posted_at' => $data->posted_at,
            'reference' => $data->reference,
            'source' => $data->source,
            'idempotency_key' => $data->idempotency_key,
            'exchange_rate' => $data->exchange_rate,
            'currency_code' => $liquidAccount->currency_code,
            'category_id' => $data->category_id,
            'entries' => [
                [
                    'account_id' => $liquidAccount->id,
                    'amount' => $this->invertAmount($amount),
                    'memo' => $data->memo,
                ],
                [
                    'account_id' => $loanReceivableAccount->id,
                    'amount' => $amount,
                ],
            ],
        ]);

        return $this->ledgerTransactionService->create($user, $transactionData);
    }

    private function findAccountForUser(User $user, int $accountId): LedgerAccount
    {
        $account = LedgerAccount::query()
            ->where('user_id', $user->id)
            ->find($accountId);

        if ($account === null) {
            throw LedgerIntegrityException::accountNotFound($accountId);
        }

        return $account;
    }

    private function validateLoanReceivableAccount(LedgerAccount $account): void
    {
        if ($account->subtype !== LedgerAccountSubType::LOAN_RECEIVABLE) {
            throw new LedgerIntegrityException(
                'Target account must be of type LOAN_RECEIVABLE.'
            );
        }
    }

    private function validateLiquidAccount(LedgerAccount $account): void
    {
        if ($account->subtype === null || ! $account->subtype->isLiquid()) {
            throw new LedgerIntegrityException(
                'Contra account must be a liquid asset (CASH, BANK, or WALLET).'
            );
        }
    }

    private function validateCurrencyMatch(LedgerAccount $account1, LedgerAccount $account2): void
    {
        if ($account1->currency_code !== $account2->currency_code) {
            throw LedgerIntegrityException::currencyMismatch();
        }
    }

    private function formatAmount(int|float|string $amount): string
    {
        $normalized = mb_ltrim((string) $amount, '+');

        if (bccomp($normalized, '0', 6) !== 1) {
            throw LedgerIntegrityException::amountMustBeNonZero();
        }

        return $normalized;
    }

    private function invertAmount(string $amount): string
    {
        return '-'.mb_ltrim($amount, '+');
    }
}
