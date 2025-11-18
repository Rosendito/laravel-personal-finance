<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Schemas;

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
                                modifyRuleUsing: static function (Unique $rule): Unique {
                                    return $rule->where('user_id', Auth::id() ?? 0);
                                }
                            ),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(self::typeOptions())
                            ->required()
                            ->native(false)
                            ->live()
                            ->enum(LedgerAccountType::class),
                        Select::make('currency_code')
                            ->label('Moneda')
                            ->relationship(
                                name: 'currency',
                                titleAttribute: 'code',
                                modifyQueryUsing: static function (Builder $query): Builder {
                                    return $query->orderBy('code');
                                }
                            )
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
                LedgerAccountType::Asset => 'Activo',
                LedgerAccountType::Liability => 'Pasivo',
                LedgerAccountType::Equity => 'Patrimonio',
                LedgerAccountType::Income => 'Ingreso',
                LedgerAccountType::Expense => 'Gasto',
            };
        }

        return $options;
    }
}
