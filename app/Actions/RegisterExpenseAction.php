<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\LedgerTransactionData;
use App\Data\Transactions\RegisterExpenseData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;
use App\Services\Queries\AccountBalanceQueryService;

final readonly class RegisterExpenseAction
{
    public function __construct(
        private LedgerTransactionService $ledgerTransactionService,
        private ResolveFundamentalAccount $resolveFundamentalAccount,
        private AccountBalanceQueryService $accountBalanceQuery,
    ) {}

    public function execute(User $user, RegisterExpenseData $data): LedgerTransaction
    {
        $paymentAccount = $this->findAccountForUser($user, $data->account_id);

        $amount = $this->formatAmount($data->amount);

        $currentBalance = $this->accountBalanceQuery->balanceForAccount($paymentAccount->id);

        if (bccomp($currentBalance, $amount, 6) === -1) {
            throw LedgerIntegrityException::insufficientFunds();
        }

        $expenseSinkAccount = $this->resolveFundamentalAccount->execute(
            $user,
            $paymentAccount->currency_code,
            LedgerAccountType::EXPENSE,
        );

        $transactionData = LedgerTransactionData::from([
            'description' => $data->description,
            'effective_at' => $data->effective_at,
            'posted_at' => $data->posted_at,
            'reference' => $data->reference,
            'source' => $data->source,
            'idempotency_key' => $data->idempotency_key,
            'exchange_rate' => $data->exchange_rate,
            'currency_code' => $paymentAccount->currency_code,
            'category_id' => $data->category_id,
            'entries' => [
                [
                    'account_id' => $paymentAccount->id,
                    'amount' => $this->invertAmount($amount),
                    'memo' => $data->memo,
                ],
                [
                    'account_id' => $expenseSinkAccount->id,
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
