<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\LedgerTransactionData;
use App\Data\Transactions\RegisterIncomeData;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;

final class RegisterIncomeAction
{
    public function __construct(
        private readonly LedgerTransactionService $ledgerTransactionService,
        private readonly ResolveFundamentalAccount $resolveFundamentalAccount,
    ) {}

    public function execute(User $user, RegisterIncomeData $data): LedgerTransaction
    {
        $depositAccount = $this->findAccountForUser($user, $data->account_id);

        $incomeSourceAccount = $this->resolveFundamentalAccount->execute(
            $user,
            $depositAccount->currency_code,
            LedgerAccountType::INCOME,
        );

        $amount = $this->formatAmount($data->amount);

        $transactionData = LedgerTransactionData::from([
            'description' => $data->description,
            'effective_at' => $data->effective_at,
            'posted_at' => $data->posted_at,
            'reference' => $data->reference,
            'source' => $data->source,
            'idempotency_key' => $data->idempotency_key,
            'exchange_rate' => $data->exchange_rate,
            'currency_code' => $depositAccount->currency_code,
            'category_id' => $data->category_id,
            'entries' => [
                [
                    'account_id' => $depositAccount->id,
                    'amount' => $amount,
                    'memo' => $data->memo,
                ],
                [
                    'account_id' => $incomeSourceAccount->id,
                    'amount' => $this->invertAmount($amount),
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
