<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\UpdateLedgerTransactionAction;
use App\Data\Transactions\UpdateLedgerTransactionData;
use App\Filament\Resources\LedgerTransactions\Schemas\LedgerTransactionForm;
use App\Models\LedgerTransaction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

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
            ->fillForm(static fn (LedgerTransaction $record): array => [
                'description' => $record->description,
                'effective_at' => $record->effective_at,
                'posted_at' => $record->posted_at,
                'reference' => $record->reference,
                'category_id' => $record->category_id,
            ])
            ->action(static function (LedgerTransaction $record, array $data): void {
                $user = Auth::user();

                if ($user === null) {
                    Notification::make()
                        ->title('Error')
                        ->body('Usuario no autenticado')
                        ->danger()
                        ->send();

                    return;
                }

                $effectiveAt = Date::parse($data['effective_at']);
                $postedAt = filled($data['posted_at'] ?? null)
                    ? Date::parse($data['posted_at'])
                    : null;

                $updateData = UpdateLedgerTransactionData::from([
                    'description' => $data['description'],
                    'effective_at' => $effectiveAt,
                    'posted_at' => $postedAt,
                    'reference' => $data['reference'] ?? null,
                    'category_id' => $data['category_id'] ?? null,
                ]);

                $action = resolve(UpdateLedgerTransactionAction::class);
                $action->execute($user, $record, $updateData);

                Notification::make()
                    ->title('Transacción actualizada')
                    ->body('La transacción se ha actualizado correctamente')
                    ->success()
                    ->send();
            });
    }
}
