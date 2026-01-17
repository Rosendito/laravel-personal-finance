<?php

declare(strict_types=1);

namespace App\Filament\Resources\Addresses\Schemas;

use App\Support\Addressing\AddressingSupport;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class AddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dirección')
                    ->description('Completa los campos requeridos según el país.')
                    ->schema([
                        Select::make('country_code')
                            ->label('País')
                            ->options(static fn (): array => AddressingSupport::countryOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->default('VE')
                            ->required()
                            ->dehydrateStateUsing(static fn (?string $state): ?string => $state === null ? null : mb_strtoupper($state)),

                        TextInput::make('administrative_area')
                            ->label('Estado / Provincia')
                            ->maxLength(100)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'administrative_area')),

                        TextInput::make('locality')
                            ->label('Ciudad / Localidad')
                            ->maxLength(150)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'locality')),

                        TextInput::make('dependent_locality')
                            ->label('Localidad dependiente')
                            ->maxLength(150)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'dependent_locality')),

                        TextInput::make('postal_code')
                            ->label('Código postal')
                            ->maxLength(32)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'postal_code')),

                        TextInput::make('sorting_code')
                            ->label('Código de clasificación')
                            ->maxLength(32)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'sorting_code')),

                        TextInput::make('address_line1')
                            ->label('Dirección (línea 1)')
                            ->maxLength(255)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'address_line1')),

                        TextInput::make('address_line2')
                            ->label('Dirección (línea 2)')
                            ->maxLength(255)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'address_line2')),

                        TextInput::make('address_line3')
                            ->label('Dirección (línea 3)')
                            ->maxLength(255)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'address_line3')),
                    ])
                    ->columns(2),

                Section::make('Contacto y etiqueta')
                    ->schema([
                        TextInput::make('organization')
                            ->label('Organización')
                            ->maxLength(255)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'organization')),

                        TextInput::make('given_name')
                            ->label('Nombre')
                            ->maxLength(100)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'given_name')),

                        TextInput::make('additional_name')
                            ->label('Segundo nombre')
                            ->maxLength(100)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'additional_name')),

                        TextInput::make('family_name')
                            ->label('Apellido')
                            ->maxLength(100)
                            ->required(static fn (Get $get): bool => AddressingSupport::isColumnRequired($get('country_code'), 'family_name')),

                        TextInput::make('label')
                            ->label('Etiqueta')
                            ->helperText('Ej: Casa, Oficina, Envíos, Facturación…')
                            ->maxLength(50),

                        Toggle::make('is_default')
                            ->label('Predeterminada')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}
