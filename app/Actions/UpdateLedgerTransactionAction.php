<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Transactions\UpdateLedgerTransactionData;
use App\Events\LedgerTransactionUpdated;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;
use Illuminate\Support\Facades\DB;

final readonly class UpdateLedgerTransactionAction
{
    public function __construct(
        private LedgerTransactionService $ledgerTransactionService,
    ) {}

    public function execute(User $user, LedgerTransaction $transaction, UpdateLedgerTransactionData $data): LedgerTransaction
    {
        $this->assertTransactionOwnership($transaction, $user);

        $category = $this->ledgerTransactionService->loadCategory($data->category_id);
        $this->ledgerTransactionService->assertCategoryOwnership($category, $user);
        $budgetPeriod = $this->ledgerTransactionService->determineBudgetPeriod($category, $data->effective_at);

        return DB::transaction(function () use ($transaction, $data, $budgetPeriod): LedgerTransaction {
            $transaction->update([
                'description' => $data->description,
                'effective_at' => $data->effective_at,
                'posted_at' => $data->posted_at,
                'reference' => $data->reference,
                'category_id' => $data->category_id,
                'budget_period_id' => $budgetPeriod?->id,
            ]);

            $transaction->load('budgetPeriod');

            event(new LedgerTransactionUpdated($transaction));

            return $transaction;
        });
    }

    private function assertTransactionOwnership(LedgerTransaction $transaction, User $user): void
    {
        if ($transaction->user_id !== $user->id) {
            throw LedgerIntegrityException::accountOwnershipMismatch();
        }
    }
}
