<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\LedgerAccountSubType;
use App\Enums\LedgerAccountType;
use App\Helpers\MoneyFormatter;
use App\Models\LedgerAccount;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;

use function sprintf;

trait HasTransactionFormComponents
{
    /**
     * Get account options with balance formatted as HTML.
     *
     * @return array<int, string>
     */
    protected static function getAccountOptions(?int $excludeAccountId = null): array
    {
        $userId = Auth::id() ?? 0;

        $query = LedgerAccount::query()
            ->where('user_id', $userId)
            ->where('type', LedgerAccountType::ASSET)
            ->where('is_archived', false)
            ->withBalance();

        if ($excludeAccountId !== null) {
            $query->where('id', '!=', $excludeAccountId);
        }

        return $query->get()
            ->mapWithKeys(static function (LedgerAccount $account): array {
                $formattedBalance = MoneyFormatter::format(
                    $account->balance ?? 0,
                    $account->currency_code ?? '',
                );

                $html = sprintf(
                    '<div><strong>%s</strong><br><span class="text-sm text-gray-500 dark:text-gray-400">%s</span></div>',
                    htmlspecialchars($account->name, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($formattedBalance, ENT_QUOTES, 'UTF-8'),
                );

                return [$account->id => $html];
            })
            ->toArray();
    }

    /**
     * Get account options filtered by subtype with balance formatted as HTML.
     *
     * @return array<int, string>
     */
    protected static function getAccountOptionsBySubtype(LedgerAccountSubType $subtype, ?int $excludeAccountId = null): array
    {
        $userId = Auth::id() ?? 0;

        $query = LedgerAccount::query()
            ->where('user_id', $userId)
            ->where('subtype', $subtype)
            ->where('is_archived', false)
            ->withBalance();

        if ($excludeAccountId !== null) {
            $query->where('id', '!=', $excludeAccountId);
        }

        return $query->get()
            ->mapWithKeys(static function (LedgerAccount $account): array {
                $formattedBalance = MoneyFormatter::format(
                    $account->balance ?? 0,
                    $account->currency_code ?? '',
                );

                $html = sprintf(
                    '<div><strong>%s</strong><br><span class="text-sm text-gray-500 dark:text-gray-400">%s</span></div>',
                    htmlspecialchars($account->name, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($formattedBalance, ENT_QUOTES, 'UTF-8'),
                );

                return [$account->id => $html];
            })
            ->toArray();
    }

    /**
     * Create account select field.
     */
    protected static function accountSelectField(
        string $name = 'account_id',
        string $label = 'Cuenta',
        ?Closure $defaultCallback = null,
    ): Select {
        return Select::make($name)
            ->label($label)
            ->options(static fn(): array => static::getAccountOptions())
            ->default($defaultCallback)
            ->searchable()
            ->preload()
            ->required()
            ->native(false)
            ->live()
            ->allowHtml();
    }

    /**
     * Create amount input field.
     */
    protected static function amountInputField(string $name = 'amount', string $label = 'Monto'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->required()
            ->minValue(0.01)
            ->step(0.01);
    }

    /**
     * Create amount input field with balance validation for expenses.
     */
    protected static function amountInputFieldWithBalanceValidation(
        string $name = 'amount',
        string $label = 'Monto',
        string $accountFieldName = 'account_id',
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->required()
            ->minValue(0.01)
            ->step(0.01)
            ->live()
            ->rules([
                function (Get $get) use ($accountFieldName) {
                    return function (string $attribute, $value, Closure $fail) use ($get, $accountFieldName): void {
                        $accountId = $get($accountFieldName);

                        if (! $accountId) {
                            return;
                        }

                        $account = LedgerAccount::query()
                            ->where('id', $accountId)
                            ->withBalance()
                            ->first();

                        if (! $account) {
                            return;
                        }

                        $balance = $account->balance ?? 0;
                        $amount = (float) $value;

                        if ($amount > $balance) {
                            $formattedBalance = MoneyFormatter::format($balance, $account->currency_code ?? '');

                            $fail(sprintf(
                                'El monto no puede ser mayor al balance disponible (%s).',
                                $formattedBalance,
                            ));
                        }
                    };
                },
            ]);
    }

    /**
     * Create amount input field with balance validation for transfers.
     */
    protected static function amountInputFieldWithTransferBalanceValidation(
        string $name = 'amount',
        string $label = 'Monto',
        string $fromAccountFieldName = 'from_account_id',
    ): TextInput {
        return self::amountInputFieldWithBalanceValidation($name, $label, $fromAccountFieldName);
    }

    /**
     * Create exchange rate input field.
     */
    protected static function exchangeRateInputField(string $accountFieldName = 'account_id'): TextInput
    {
        return TextInput::make('exchange_rate')
            ->label(sprintf('Tasa de cambio (%s)', config('finance.currency.default')))
            ->numeric()
            ->minValue(0.000001)
            ->visible(function (Get $get) use ($accountFieldName): bool {
                $accountId = $get($accountFieldName);

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
            ->required(function (Get $get) use ($accountFieldName): bool {
                $accountId = $get($accountFieldName);

                if (! $accountId) {
                    return false;
                }

                $defaultCurrency = config('finance.currency.default');
                $account = LedgerAccount::find($accountId);

                if (! $account) {
                    return false;
                }

                return $account->currency_code !== $defaultCurrency;
            });
    }

    /**
     * Create exchange rate input field for transfer (two accounts).
     */
    protected static function exchangeRateInputFieldForTransfer(): TextInput
    {
        return TextInput::make('exchange_rate')
            ->label(sprintf('Tasa de cambio (%s)', config('finance.currency.default')))
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
            });
    }

    /**
     * Create exchange rate input field for debt/loan transactions (two accounts).
     */
    protected static function exchangeRateInputFieldForDebtLoan(
        string $targetAccountFieldName = 'target_account_id',
        string $contraAccountFieldName = 'contra_account_id',
    ): TextInput {
        return TextInput::make('exchange_rate')
            ->label(sprintf('Tasa de cambio (%s)', config('finance.currency.default')))
            ->numeric()
            ->minValue(0.000001)
            ->visible(function (Get $get) use ($targetAccountFieldName, $contraAccountFieldName): bool {
                $targetId = $get($targetAccountFieldName);
                $contraId = $get($contraAccountFieldName);

                if (! $targetId || ! $contraId) {
                    return false;
                }

                $defaultCurrency = config('finance.currency.default');
                $targetAccount = LedgerAccount::find($targetId);
                $contraAccount = LedgerAccount::find($contraId);

                if (! $targetAccount || ! $contraAccount) {
                    return false;
                }

                return $targetAccount->currency_code !== $defaultCurrency
                    && $contraAccount->currency_code !== $defaultCurrency;
            })
            ->required(function (Get $get) use ($targetAccountFieldName, $contraAccountFieldName): bool {
                $targetId = $get($targetAccountFieldName);
                $contraId = $get($contraAccountFieldName);

                if (! $targetId || ! $contraId) {
                    return false;
                }

                $defaultCurrency = config('finance.currency.default');
                $targetAccount = LedgerAccount::find($targetId);
                $contraAccount = LedgerAccount::find($contraId);

                if (! $targetAccount || ! $contraAccount) {
                    return false;
                }

                return $targetAccount->currency_code !== $defaultCurrency
                    && $contraAccount->currency_code !== $defaultCurrency;
            });
    }

    /**
     * Create description input field.
     */
    protected static function descriptionInputField(): TextInput
    {
        return TextInput::make('description')
            ->label('Descripción')
            ->required()
            ->maxLength(255);
    }

    /**
     * Create effective date time picker field.
     */
    protected static function effectiveAtDateTimePickerField(): DateTimePicker
    {
        return DateTimePicker::make('effective_at')
            ->label('Fecha efectiva')
            ->required()
            ->default(now())
            ->native(false);
    }

    /**
     * Create additional information section.
     */
    protected static function additionalInformationSection(): Section
    {
        return Section::make('Información adicional')
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
            ->collapsed();
    }
}
