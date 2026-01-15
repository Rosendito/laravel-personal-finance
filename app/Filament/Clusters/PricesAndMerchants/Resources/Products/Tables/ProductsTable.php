<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand')
                    ->label('Marca')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('canonical_size')
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
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('barcode')
                    ->label('Código de barras')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
