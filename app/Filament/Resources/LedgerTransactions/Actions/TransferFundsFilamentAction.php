<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\TransferFundsAction;
use App\Concerns\HasTransactionFormComponents;
use App\Data\Transactions\TransferFundsData;
use App\Models\LedgerAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class TransferFundsFilamentAction
{
    use HasTransactionFormComponents;

    public static function make(): Action
    {
        return Action::make('transferFunds')
            ->label('Transferir Fondos')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->modalHeading('Transferir Fondos')
            ->schema([
                self::accountSelectField(
                    name: 'from_account_id',
                    label: 'Cuenta origen',
                ),
                Select::make('to_account_id')
                    ->label('Cuenta destino')
                    ->options(static function (callable $get): array {
                        $fromAccountId = $get('from_account_id');

                        return self::getAccountOptions($fromAccountId);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->live()
                    ->allowHtml(),
                self::amountInputFieldWithTransferBalanceValidation(),
                TextInput::make('to_amount')
                    ->label('Monto destino')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->visible(function (Get $get): bool {
                        $fromId = $get('from_account_id');
                        $toId = $get('to_account_id');

                        if (! $fromId || ! $toId) {
                            return false;
                        }

                        $fromAccount = LedgerAccount::find($fromId);
                        $toAccount = LedgerAccount::find($toId);

                        if (! $fromAccount || ! $toAccount) {
                            return false;
                        }

                        return $fromAccount->currency_code !== $toAccount->currency_code;
                    })
                    ->required(function (Get $get): bool {
                        $fromId = $get('from_account_id');
                        $toId = $get('to_account_id');

                        if (! $fromId || ! $toId) {
                            return false;
                        }

                        $fromAccount = LedgerAccount::find($fromId);
                        $toAccount = LedgerAccount::find($toId);

                        if (! $fromAccount || ! $toAccount) {
                            return false;
                        }

                        return $fromAccount->currency_code !== $toAccount->currency_code;
                    }),
                self::exchangeRateInputFieldForTransfer(),
                self::descriptionInputField(),
                self::effectiveAtDateTimePickerField(),
                self::additionalInformationSection(),
            ])
            ->action(function (array $data): void {
                $user = Auth::user();

                if ($user === null) {
                    Notification::make()
                        ->title('Error')
                        ->body('Usuario no autenticado')
                        ->danger()
                        ->send();

                    return;
                }

                $effectiveAt = Carbon::parse($data['effective_at']);
                $postedAt = filled($data['posted_at'] ?? null)
                    ? Carbon::parse($data['posted_at'])
                    : null;

                $transferFundsData = TransferFundsData::from([
                    'description' => $data['description'],
                    'effective_at' => $effectiveAt,
                    'from_account_id' => $data['from_account_id'],
                    'to_account_id' => $data['to_account_id'],
                    'amount' => $data['amount'],
                    'to_amount' => $data['to_amount'] ?? null,
                    'exchange_rate' => $data['exchange_rate'] ?? null,
                    'posted_at' => $postedAt,
                    'memo' => $data['memo'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'source' => 'manual',
                ]);

                $action = app(TransferFundsAction::class);
                $action->execute($user, $transferFundsData);

                Notification::make()
                    ->title('Transferencia realizada')
                    ->body('La transferencia se ha realizado correctamente')
                    ->success()
                    ->send();
            });
    }
}
