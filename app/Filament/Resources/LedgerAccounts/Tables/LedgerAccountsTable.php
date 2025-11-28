<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Tables;

use App\Enums\LedgerAccountSubType;
use App\Enums\LedgerAccountType;
use App\Helpers\MoneyFormatter;
use App\Models\LedgerAccount;
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
                        LedgerAccountType::ASSET => 'Activo',
                        LedgerAccountType::LIABILITY => 'Pasivo',
                        LedgerAccountType::EQUITY => 'Patrimonio',
                        LedgerAccountType::INCOME => 'Ingreso',
                        LedgerAccountType::EXPENSE => 'Gasto',
                        default => 'Desconocido',
                    })
                    ->color(static fn (?LedgerAccountType $state): string => match ($state) {
                        LedgerAccountType::ASSET => 'success',
                        LedgerAccountType::LIABILITY => 'danger',
                        LedgerAccountType::EQUITY => 'info',
                        LedgerAccountType::INCOME => 'success',
                        LedgerAccountType::EXPENSE => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('subtype')
                    ->label('Subtipo')
                    ->badge()
                    ->formatStateUsing(static fn (?LedgerAccountSubType $state): string => match ($state) {
                        LedgerAccountSubType::CASH => 'Efectivo',
                        LedgerAccountSubType::BANK => 'Banco',
                        LedgerAccountSubType::WALLET => 'Billetera Digital',
                        LedgerAccountSubType::LOAN_RECEIVABLE => 'Préstamo por Cobrar',
                        LedgerAccountSubType::INVESTMENT => 'Inversión',
                        LedgerAccountSubType::LOAN_PAYABLE => 'Préstamo por Pagar',
                        LedgerAccountSubType::CREDIT_CARD => 'Tarjeta de Crédito',
                        default => '-',
                    })
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->state(static fn (LedgerAccount $record): string => MoneyFormatter::format(
                        $record->balance ?? 0,
                        $record->currency_code ?? '',
                    ))
                    ->alignRight()
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
                        LedgerAccountType::ASSET->value => 'Activo',
                        LedgerAccountType::LIABILITY->value => 'Pasivo',
                        LedgerAccountType::EQUITY->value => 'Patrimonio',
                        LedgerAccountType::INCOME->value => 'Ingreso',
                        LedgerAccountType::EXPENSE->value => 'Gasto',
                    ]),
                SelectFilter::make('subtype')
                    ->label('Subtipo')
                    ->options([
                        LedgerAccountSubType::CASH->value => 'Efectivo',
                        LedgerAccountSubType::BANK->value => 'Banco',
                        LedgerAccountSubType::WALLET->value => 'Billetera Digital',
                        LedgerAccountSubType::LOAN_RECEIVABLE->value => 'Préstamo por Cobrar',
                        LedgerAccountSubType::INVESTMENT->value => 'Inversión',
                        LedgerAccountSubType::LOAN_PAYABLE->value => 'Préstamo por Pagar',
                        LedgerAccountSubType::CREDIT_CARD->value => 'Tarjeta de Crédito',
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
