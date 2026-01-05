<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Schemas;

use App\Enums\LedgerAccountSubType;
use App\Enums\LedgerAccountType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

final class LedgerAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la cuenta')
                    ->description('Gestiona tus cuentas contables y su configuración.')
                    ->schema([
                        Hidden::make('user_id')
                            ->default(static fn (): ?int => Auth::id())
                            ->required(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej. Cuenta Corriente')
                            ->required()
                            ->rule('string')
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: static fn (Unique $rule): Unique => $rule->where('user_id', Auth::id() ?? 0)
                            ),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(self::typeOptions())
                            ->required()
                            ->native(false)
                            ->live()
                            ->enum(LedgerAccountType::class)
                            ->afterStateUpdated(static function ($state, callable $set): void {
                                $set('subtype', null);
                            }),
                        Select::make('subtype')
                            ->label('Subtipo')
                            ->options(static fn (callable $get): array => self::subtypeOptions($get('type')))
                            ->native(false)
                            ->live()
                            ->enum(LedgerAccountSubType::class)
                            ->visible(static fn (callable $get): bool => self::hasSubtypes($get('type')))
                            ->required(static fn (callable $get): bool => self::hasSubtypes($get('type'))),
                        Select::make('currency_code')
                            ->label('Moneda')
                            ->relationship(
                                name: 'currency',
                                titleAttribute: 'code',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->orderBy('code')
                            )
                            ->default(static fn (): string => config('finance.currency.default'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),
                        Toggle::make('is_archived')
                            ->label('Archivada')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (LedgerAccountType::cases() as $type) {
            $options[$type->value] = match ($type) {
                LedgerAccountType::ASSET => 'Activo',
                LedgerAccountType::LIABILITY => 'Pasivo',
                LedgerAccountType::EQUITY => 'Patrimonio',
                LedgerAccountType::INCOME => 'Ingreso',
                LedgerAccountType::EXPENSE => 'Gasto',
            };
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function subtypeOptions(?LedgerAccountType $type): array
    {
        if (! $type instanceof LedgerAccountType) {
            return [];
        }

        $subtypes = $type->subtypes();
        $options = [];

        foreach ($subtypes as $subtype) {
            $options[$subtype->value] = match ($subtype) {
                LedgerAccountSubType::CASH => 'Efectivo',
                LedgerAccountSubType::BANK => 'Banco',
                LedgerAccountSubType::WALLET => 'Billetera Digital',
                LedgerAccountSubType::LOAN_RECEIVABLE => 'Préstamo por Cobrar',
                LedgerAccountSubType::INVESTMENT => 'Inversión',
                LedgerAccountSubType::LOAN_PAYABLE => 'Préstamo por Pagar',
                LedgerAccountSubType::CREDIT_CARD => 'Tarjeta de Crédito',
            };
        }

        return $options;
    }

    private static function hasSubtypes(?LedgerAccountType $type): bool
    {
        if (! $type instanceof LedgerAccountType) {
            return false;
        }

        return $type->subtypes() !== [];
    }
}
