<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Actions\UpdateLedgerTransactionAction;
use App\Data\Transactions\UpdateLedgerTransactionData;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        if ($user === null) {
            return parent::handleRecordUpdate($record, $data);
        }

        $effectiveAt = Carbon::parse($data['effective_at']);
        $postedAt = filled($data['posted_at'] ?? null)
            ? Carbon::parse($data['posted_at'])
            : null;

        $updateData = UpdateLedgerTransactionData::from([
            'description' => $data['description'],
            'effective_at' => $effectiveAt,
            'posted_at' => $postedAt,
            'reference' => $data['reference'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ]);

        $action = app(UpdateLedgerTransactionAction::class);
        $action->execute($user, $record, $updateData);

        return $record->fresh();
    }
}
