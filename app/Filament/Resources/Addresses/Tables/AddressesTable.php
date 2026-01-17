<?php

declare(strict_types=1);

namespace App\Filament\Resources\Addresses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AddressesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('label')
                    ->label('Etiqueta')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country_code')
                    ->label('País')
                    ->sortable(),

                TextColumn::make('administrative_area')
                    ->label('Estado / Provincia')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('locality')
                    ->label('Ciudad')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('postal_code')
                    ->label('Código postal')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('address_line1')
                    ->label('Dirección')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_default')
                    ->label('Predeterminada')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
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
