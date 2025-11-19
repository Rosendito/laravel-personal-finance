<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Filament\Resources\LedgerTransactions\Schemas\LedgerTransactionForm;
use App\Models\LedgerTransaction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

final class EditLedgerTransactionFilamentAction
{
    public static function make(): Action
    {
        return Action::make('edit')
            ->label('Editar')
            ->icon('heroicon-o-pencil')
            ->color('primary')
            ->modalHeading('Editar Transacción')
            ->schema(LedgerTransactionForm::getComponents())
            ->fillForm(static function (LedgerTransaction $record): array {
                return [
                    'description' => $record->description,
                    'effective_at' => $record->effective_at,
                    'posted_at' => $record->posted_at,
                    'reference' => $record->reference,
                ];
            })
            ->action(static function (LedgerTransaction $record, array $data): void {
                $record->update([
                    'description' => $data['description'],
                    'effective_at' => $data['effective_at'],
                    'posted_at' => $data['posted_at'] ?? null,
                    'reference' => $data['reference'] ?? null,
                ]);

                Notification::make()
                    ->title('Transacción actualizada')
                    ->body('La transacción se ha actualizado correctamente')
                    ->success()
                    ->send();
            });
    }
}
