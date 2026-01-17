<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del producto')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('brand')
                            ->label('Marca')
                            ->placeholder('—'),
                        TextEntry::make('category')
                            ->label('Categoría')
                            ->placeholder('—'),
                        TextEntry::make('canonical_size')
                            ->label('Tamaño canónico')
                            ->state(static function (Product $record): string {
                                $value = $record->canonical_size_value;
                                $unit = $record->canonical_size_unit;

                                if ($value === null && ($unit === null || $unit === '')) {
                                    return '—';
                                }

                                $valueString = $value === null ? '' : (string) $value;
                                $unitString = $unit ?? '';

                                return mb_trim($valueString.' '.$unitString);
                            }),
                        TextEntry::make('barcode')
                            ->label('Código de barras')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
