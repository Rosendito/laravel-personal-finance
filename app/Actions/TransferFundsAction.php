<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Transactions\TransferFundsData;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerTransactionService;
use LogicException;

final class TransferFundsAction
{
    public function __construct(
        private readonly LedgerTransactionService $ledgerTransactionService,
    ) {}

    public function execute(User $user, TransferFundsData $data): LedgerTransaction
    {
        throw new LogicException('Transfer action not implemented yet.');
    }
}
