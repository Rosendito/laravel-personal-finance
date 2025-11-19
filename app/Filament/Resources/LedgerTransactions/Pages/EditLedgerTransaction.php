<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use Filament\Resources\Pages\EditRecord;

final class EditLedgerTransaction extends EditRecord
{
    protected static string $resource = LedgerTransactionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        $record->load([
            'entries.account',
            'entries.category',
            'budgetPeriod.budget',
        ]);

        return $data;
    }
}
