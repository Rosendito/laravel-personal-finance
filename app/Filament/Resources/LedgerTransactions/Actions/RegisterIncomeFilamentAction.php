<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\RegisterIncomeAction;
use App\Concerns\HasTransactionFormComponents;
use App\Data\Transactions\RegisterIncomeData;
use App\Enums\CategoryType;
use App\Enums\LedgerAccountType;
use App\Models\Category;
use App\Models\LedgerAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

final class RegisterIncomeFilamentAction
{
    use HasTransactionFormComponents;

    public static function make(): Action
    {
        return Action::make('registerIncome')
            ->label('Registrar Ingreso')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->modalHeading('Registrar Ingreso')
            ->schema([
                self::accountSelectField(
                    defaultCallback: static function (): ?int {
                        $userId = Auth::id();

                        if ($userId === null) {
                            return null;
                        }

                        $account = LedgerAccount::query()
                            ->where('user_id', $userId)
                            ->where('type', LedgerAccountType::ASSET)
                            ->where('is_archived', false)
                            ->withMostIncomeTransactions()
                            ->first();

                        return $account?->id;
                    },
                ),
                self::amountInputField(),
                self::exchangeRateInputField(),
                self::descriptionInputField(),
                self::effectiveAtDateTimePickerField(),
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

                $effectiveAt = Date::parse($data['effective_at']);
                $postedAt = filled($data['posted_at'] ?? null)
                    ? Date::parse($data['posted_at'])
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

                $action = resolve(RegisterIncomeAction::class);
                $action->execute($user, $registerIncomeData);

                Notification::make()
                    ->title('Ingreso registrado')
                    ->body('El ingreso se ha registrado correctamente')
                    ->success()
                    ->send();
            });
    }
}
