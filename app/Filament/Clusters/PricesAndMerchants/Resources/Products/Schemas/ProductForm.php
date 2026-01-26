<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles del producto')
                    ->description('Define el producto base para comparar precios entre comercios.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej. Leche entera')
                            ->required()
                            ->rule('string')
                            ->maxLength(255),
                        TextInput::make('brand')
                            ->label('Marca')
                            ->placeholder('Ej. Alpina')
                            ->nullable()
                            ->rule('string')
                            ->maxLength(255),
                        TextInput::make('category')
                            ->label('Categoría')
                            ->placeholder('Ej. Lácteos')
                            ->nullable()
                            ->rule('string')
                            ->maxLength(255),
                        TextInput::make('canonical_size_value')
                            ->label('Tamaño canónico (valor)')
                            ->placeholder('Ej. 1.000000')
                            ->nullable()
                            ->numeric()
                            ->rule('numeric'),
                        Select::make('canonical_size_unit')
                            ->label('Tamaño canónico (unidad)')
                            ->nullable()
                            ->options(Product::canonicalSizeUnitOptions())
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->placeholder('Selecciona una unidad'),
                        TextInput::make('barcode')
                            ->label('Código de barras')
                            ->placeholder('Opcional')
                            ->nullable()
                            ->rule('string')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Opcional: detalles adicionales del producto')
                            ->nullable()
                            ->rule('string')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
