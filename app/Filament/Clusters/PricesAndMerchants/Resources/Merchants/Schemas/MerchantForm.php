<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Schemas;

use App\Enums\MerchantType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles del comercio')
                    ->description('Define el comercio donde se registran precios y listados de productos.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej. Éxito')
                            ->required()
                            ->rule('string')
                            ->maxLength(255),
                        Select::make('merchant_type')
                            ->label('Tipo')
                            ->options(MerchantType::class)
                            ->required()
                            ->native(false)
                            ->preload()
                            ->placeholder('Selecciona un tipo'),
                        TextInput::make('base_url')
                            ->label('URL base')
                            ->placeholder('Ej. https://www.example.com')
                            ->nullable()
                            ->rule('string')
                            ->maxLength(255)
                            ->url(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Opcional: detalles adicionales del comercio')
                            ->nullable()
                            ->rule('string')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
