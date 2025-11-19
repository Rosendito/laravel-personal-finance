<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\RegisterIncomeAction;
use App\Data\Transactions\RegisterIncomeData;
use App\Enums\CategoryType;
use App\Enums\LedgerAccountType;
use App\Models\Category;
use App\Models\LedgerAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class RegisterIncomeFilamentAction
{
    public static function make(): Action
    {
        return Action::make('registerIncome')
            ->label('Registrar Ingreso')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->modalHeading('Registrar Ingreso')
            ->schema([
                Select::make('account_id')
                    ->label('Cuenta')
                    ->options(static function (): array {
                        $userId = Auth::id() ?? 0;

                        return LedgerAccount::query()
                            ->where('user_id', $userId)
                            ->where('type', LedgerAccountType::Asset)
                            ->where('is_archived', false)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default(static function (): ?int {
                        $userId = Auth::id();

                        if ($userId === null) {
                            return null;
                        }

                        $account = LedgerAccount::query()
                            ->where('user_id', $userId)
                            ->where('type', LedgerAccountType::Asset)
                            ->where('is_archived', false)
                            ->withMostIncomeTransactions()
                            ->first();

                        return $account?->id;
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
                TextInput::make('exchange_rate')
                    ->label('Tasa de cambio')
                    ->numeric()
                    ->minValue(0.000001)
                    ->visible(function (Get $get): bool {
                        $accountId = $get('account_id');

                        if (! $accountId) {
                            return false;
                        }

                        $defaultCurrency = config('finance.currency.default');
                        $account = LedgerAccount::find($accountId);

                        if (! $account) {
                            return false;
                        }

                        return $account->currency_code !== $defaultCurrency;
                    })
                    ->required(function (Get $get): bool {
                        $accountId = $get('account_id');

                        if (! $accountId) {
                            return false;
                        }

                        $defaultCurrency = config('finance.currency.default');
                        $account = LedgerAccount::find($accountId);

                        if (! $account) {
                            return false;
                        }

                        return $account->currency_code !== $defaultCurrency;
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
                Select::make('category_id')
                    ->label('Categoría')
                    ->options(static function (): array {
                        $userId = Auth::id() ?? 0;

                        return Category::query()
                            ->where('user_id', $userId)
                            ->where('type', CategoryType::Income)
                            ->where('is_archived', false)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false),
                Section::make('Información adicional')
                    ->description('Campos opcionales adicionales')
                    ->schema([
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
                    ->collapsible()
                    ->collapsed(),
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

                $registerIncomeData = RegisterIncomeData::from([
                    'description' => $data['description'],
                    'effective_at' => $effectiveAt,
                    'account_id' => $data['account_id'],
                    'amount' => $data['amount'],
                    'exchange_rate' => $data['exchange_rate'] ?? null,
                    'posted_at' => $postedAt,
                    'category_id' => $data['category_id'] ?? null,
                    'memo' => $data['memo'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'source' => 'manual',
                ]);

                $action = app(RegisterIncomeAction::class);
                $action->execute($user, $registerIncomeData);

                Notification::make()
                    ->title('Ingreso registrado')
                    ->body('El ingreso se ha registrado correctamente')
                    ->success()
                    ->send();
            });
    }
}
