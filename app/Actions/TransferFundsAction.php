<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\LedgerTransactionData;
use App\Data\Transactions\TransferFundsData;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;
use App\Services\Queries\AccountBalanceQueryService;

final readonly class TransferFundsAction
{
    public function __construct(
        private LedgerTransactionService $ledgerTransactionService,
        private AccountBalanceQueryService $accountBalanceQuery,
    ) {}

    public function execute(User $user, TransferFundsData $data): LedgerTransaction
    {
        $fromAccount = $this->findAccountForUser($user, $data->from_account_id);
        $toAccount = $this->findAccountForUser($user, $data->to_account_id);

        $amount = $this->formatAmount($data->amount);

        $currentBalance = $this->accountBalanceQuery->balanceForAccount($fromAccount->id);

        if (bccomp($currentBalance, $amount, 6) === -1) {
            throw LedgerIntegrityException::insufficientFunds();
        }

        $toAmount = $data->to_amount !== null ? $this->formatAmount($data->to_amount) : $amount;

        $transactionData = LedgerTransactionData::from([
            'description' => $data->description,
            'effective_at' => $data->effective_at,
            'posted_at' => $data->posted_at,
            'reference' => $data->reference,
            'source' => $data->source,
            'idempotency_key' => $data->idempotency_key,
            'exchange_rate' => $data->exchange_rate,
            'currency_code' => $fromAccount->currency_code,
            'entries' => [
                [
                    'account_id' => $fromAccount->id,
                    'amount' => $this->invertAmount($amount),
                    'memo' => $data->memo,
                ],
                [
                    'account_id' => $toAccount->id,
                    'amount' => $toAmount,
                    'memo' => $data->memo,
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
