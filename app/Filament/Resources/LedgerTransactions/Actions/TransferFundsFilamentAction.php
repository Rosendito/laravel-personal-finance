<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\TransferFundsAction;
use App\Data\Transactions\TransferFundsData;
use App\Enums\LedgerAccountType;
use App\Models\LedgerAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class TransferFundsFilamentAction
{
    public static function make(): Action
    {
        return Action::make('transferFunds')
            ->label('Transferir Fondos')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->modalHeading('Transferir Fondos')
            ->schema([
                Select::make('from_account_id')
                    ->label('Cuenta origen')
                    ->options(static function (): array {
                        $userId = Auth::id() ?? 0;

                        return LedgerAccount::query()
                            ->where('user_id', $userId)
                            ->where('type', LedgerAccountType::Asset)
                            ->where('is_archived', false)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->live(),
                Select::make('to_account_id')
                    ->label('Cuenta destino')
                    ->options(static function (callable $get): array {
                        $userId = Auth::id() ?? 0;
                        $fromAccountId = $get('from_account_id');

                        return LedgerAccount::query()
                            ->where('user_id', $userId)
                            ->where('type', LedgerAccountType::Asset)
                            ->where('is_archived', false)
                            ->where('id', '!=', $fromAccountId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->live(),
                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),
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
                TextInput::make('exchange_rate')
                    ->label('Tasa de cambio')
                    ->numeric()
                    ->minValue(0.000001)
                    ->visible(function (Get $get): bool {
                        $fromId = $get('from_account_id');
                        $toId = $get('to_account_id');

                        if (! $fromId || ! $toId) {
                            return false;
                        }

                        $defaultCurrency = config('finance.currency.default');
                        $fromAccount = LedgerAccount::find($fromId);
                        $toAccount = LedgerAccount::find($toId);

                        if (! $fromAccount || ! $toAccount) {
                            return false;
                        }

                        return $fromAccount->currency_code !== $defaultCurrency
                            && $toAccount->currency_code !== $defaultCurrency;
                    })
                    ->required(function (Get $get): bool {
                        $fromId = $get('from_account_id');
                        $toId = $get('to_account_id');

                        if (! $fromId || ! $toId) {
                            return false;
                        }

                        $defaultCurrency = config('finance.currency.default');
                        $fromAccount = LedgerAccount::find($fromId);
                        $toAccount = LedgerAccount::find($toId);

                        if (! $fromAccount || ! $toAccount) {
                            return false;
                        }

                        return $fromAccount->currency_code !== $defaultCurrency
                            && $toAccount->currency_code !== $defaultCurrency;
                    }),
                TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('effective_at')
                    ->label('Fecha efectiva')
                    ->required()
                    ->default(now())
                    ->native(false),
                DatePicker::make('posted_at')
                    ->label('Fecha publicación')
                    ->native(false),
                Textarea::make('memo')
                    ->label('Memo')
                    ->rows(3)
                    ->maxLength(500),
                TextInput::make('reference')
                    ->label('Referencia')
                    ->maxLength(255),
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
            })
            ->successNotificationTitle('Transferencia realizada correctamente');
    }
}
