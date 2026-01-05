<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Actions;

use App\Actions\Debts\RegisterBorrowingRepaymentAction;
use App\Concerns\HasTransactionFormComponents;
use App\Data\Transactions\RegisterDebtData;
use App\Models\LedgerAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

final class PayDebtFilamentAction
{
    use HasTransactionFormComponents;

    public static function make(?int $accountId = null): Action
    {
        return Action::make('payDebt')
            ->label('Pagar Deuda')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('danger')
            ->modalHeading('Pagar Deuda')
            ->fillForm(fn (array $arguments): array => [
                'target_account_id' => $accountId ?? $arguments['accountId'] ?? null,
                'effective_at' => Date::now()->toDateTimeString(),
            ])
            ->schema([
                Hidden::make('target_account_id')
                    ->required(),
                self::accountSelectField(
                    name: 'contra_account_id',
                    label: 'Cuenta Origen (Liquid)',
                ),
                self::amountInputFieldWithBalanceValidation(
                    accountFieldName: 'contra_account_id',
                ),
                self::exchangeRateInputFieldForDebtLoan(
                    targetAccountFieldName: 'target_account_id',
                    contraAccountFieldName: 'contra_account_id',
                ),
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

                $targetAccountId = $data['target_account_id'];
                $targetAccount = LedgerAccount::query()->find($targetAccountId);
                $contraAccount = LedgerAccount::query()->find($data['contra_account_id']);

                if ($targetAccount === null || $contraAccount === null) {
                    Notification::make()
                        ->title('Error')
                        ->body('Una o ambas cuentas no fueron encontradas')
                        ->danger()
                        ->send();

                    return;
                }

                if ($targetAccount->currency_code !== $contraAccount->currency_code) {
                    Notification::make()
                        ->title('Error')
                        ->body('Las cuentas deben tener la misma moneda')
                        ->danger()
                        ->send();

                    return;
                }

                $effectiveAt = Date::parse($data['effective_at']);
                $postedAt = filled($data['posted_at'] ?? null)
                    ? Date::parse($data['posted_at'])
                    : null;

                $registerDebtData = RegisterDebtData::from([
                    'description' => $data['description'],
                    'effective_at' => $effectiveAt,
                    'target_account_id' => $targetAccountId,
                    'contra_account_id' => $data['contra_account_id'],
                    'amount' => $data['amount'],
                    'exchange_rate' => $data['exchange_rate'] ?? null,
                    'posted_at' => $postedAt,
                    'category_id' => null,
                    'memo' => $data['memo'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'source' => 'manual',
                ]);

                $action = resolve(RegisterBorrowingRepaymentAction::class);
                $action->execute($user, $registerDebtData);

                Notification::make()
                    ->title('Pago registrado')
                    ->body('El pago de la deuda se ha registrado correctamente')
                    ->success()
                    ->send();
            });
    }
}
