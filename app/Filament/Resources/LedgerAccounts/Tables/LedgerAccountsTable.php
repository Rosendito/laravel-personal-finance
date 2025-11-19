<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Tables;

use App\Enums\LedgerAccountType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class LedgerAccountsTable
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
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(static fn (?LedgerAccountType $state): string => match ($state) {
                        LedgerAccountType::Asset => 'Activo',
                        LedgerAccountType::Liability => 'Pasivo',
                        LedgerAccountType::Equity => 'Patrimonio',
                        LedgerAccountType::Income => 'Ingreso',
                        LedgerAccountType::Expense => 'Gasto',
                        default => 'Desconocido',
                    })
                    ->color(static fn (?LedgerAccountType $state): string => match ($state) {
                        LedgerAccountType::Asset => 'success',
                        LedgerAccountType::Liability => 'danger',
                        LedgerAccountType::Equity => 'info',
                        LedgerAccountType::Income => 'success',
                        LedgerAccountType::Expense => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Moneda')
                    ->sortable(),
                TextColumn::make('entries_count')
                    ->label('Movimientos')
                    ->counts('entries')
                    ->formatStateUsing(static fn (?int $state): string => (string) ($state ?? 0))
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_archived')
                    ->label('Archivada')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('is_fundamental')
                    ->label('Fundamental')
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        LedgerAccountType::Asset->value => 'Activo',
                        LedgerAccountType::Liability->value => 'Pasivo',
                        LedgerAccountType::Equity->value => 'Patrimonio',
                        LedgerAccountType::Income->value => 'Ingreso',
                        LedgerAccountType::Expense->value => 'Gasto',
                    ]),
                SelectFilter::make('currency_code')
                    ->label('Moneda')
                    ->relationship(
                        name: 'currency',
                        titleAttribute: 'code',
                        modifyQueryUsing: static function (Builder $query): Builder {
                            return $query->orderBy('code');
                        }
                    ),
                TernaryFilter::make('is_archived')
                    ->label('Estado')
                    ->nullable()
                    ->placeholder('Todas')
                    ->trueLabel('Archivadas')
                    ->falseLabel('Activas'),
                TernaryFilter::make('is_fundamental')
                    ->label('Fundamental')
                    ->placeholder('Todas')
                    ->trueLabel('Fundamentales')
                    ->falseLabel('No Fundamentales')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_fundamental', true),
                        false: fn (Builder $query) => $query->where('is_fundamental', false),
                    )
                    ->default(false),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
