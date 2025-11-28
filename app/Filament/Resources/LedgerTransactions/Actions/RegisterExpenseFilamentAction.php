<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\RegisterExpenseAction;
use App\Concerns\HasTransactionFormComponents;
use App\Data\Transactions\RegisterExpenseData;
use App\Enums\CategoryType;
use App\Enums\LedgerAccountType;
use App\Models\Category;
use App\Models\LedgerAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class RegisterExpenseFilamentAction
{
    use HasTransactionFormComponents;

    public static function make(): Action
    {
        return Action::make('registerExpense')
            ->label('Registrar Gasto')
            ->icon('heroicon-o-minus-circle')
            ->color('danger')
            ->modalHeading('Registrar Gasto')
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
                            ->withMostExpenseTransactions()
                            ->first();

                        return $account?->id;
                    },
                ),
                self::amountInputFieldWithBalanceValidation(),
                self::exchangeRateInputField(),
                self::descriptionInputField(),
                self::effectiveAtDateTimePickerField(),
                Select::make('category_id')
                    ->label('Categoría')
                    ->options(static function (): array {
                        $userId = Auth::id() ?? 0;

                        return Category::query()
                            ->where('user_id', $userId)
                            ->where('type', CategoryType::Expense)
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

                $effectiveAt = Carbon::parse($data['effective_at']);
                $postedAt = filled($data['posted_at'] ?? null)
                    ? Carbon::parse($data['posted_at'])
                    : null;

                $registerExpenseData = RegisterExpenseData::from([
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

                $action = app(RegisterExpenseAction::class);
                $action->execute($user, $registerExpenseData);

                Notification::make()
                    ->title('Gasto registrado')
                    ->body('El gasto se ha registrado correctamente')
                    ->success()
                    ->send();
            });
    }
}
