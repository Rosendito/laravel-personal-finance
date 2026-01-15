<?php

declare(strict_types=1);

namespace App\Filament\Resources\Budgets\Tables;

use App\Models\Budget;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with(['currentPeriod.aggregates']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periods_count')
                    ->label('Periods')
                    ->counts('periods')
                    ->formatStateUsing(static fn (?int $state): string => (string) ($state ?? 0))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('currentPeriod.amount')
                    ->label('Budgeted')
                    ->numeric(2)
                    ->state(static fn (Budget $record): ?string => $record->currentPeriod?->amount)
                    ->sortable(query: static fn (Builder $query, string $direction): Builder => $query->join('budget_periods', 'budgets.id', '=', 'budget_periods.budget_id')
                        ->orderBy('budget_periods.amount', $direction)
                        ->select('budgets.*')),
                TextColumn::make('currentPeriod.spent_amount')
                    ->label('Spent')
                    ->numeric(2)
                    ->state(static fn (Budget $record): string => $record->currentPeriod?->spent_amount ?? '0')
                    ->color(static function (Budget $record, mixed $state): string {
                        $period = $record->currentPeriod;

                        if ($period === null) {
                            return 'gray';
                        }

                        $spent = (float) $state;
                        $amount = (float) $period->amount;

                        if ($spent > $amount) {
                            return 'danger';
                        }

                        if ($spent > $amount * 0.9) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                TextColumn::make('currentPeriod.remaining_amount')
                    ->label('Remaining')
                    ->numeric(2)
                    ->state(static fn (Budget $record): string => $record->currentPeriod?->remaining_amount ?? '0')
                    ->color(static function (Budget $record, mixed $state): string {
                        $remaining = (float) $state;

                        if ($remaining < 0) {
                            return 'danger';
                        }

                        return 'success';
                    }),
                TextColumn::make('currentPeriod.usage_percent')
                    ->label('% Used')
                    ->state(static fn (Budget $record): string => $record->currentPeriod?->usage_percent ?? '0')
                    ->suffix('%')
                    ->color(static function (Budget $record, mixed $state): string {
                        $percent = (float) $state;

                        if ($percent > 100) {
                            return 'danger';
                        }

                        if ($percent > 90) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->nullable()
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
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
