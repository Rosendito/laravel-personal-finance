<?php

declare(strict_types=1);

namespace App\Filament\Resources\Budgets\RelationManagers;

use App\Models\BudgetPeriod;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use RuntimeException;

final class PeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';

    public function form(Schema $schema): Schema
    {
        $ownerRecord = $this->getOwnerRecord();
        $lastPeriod = $ownerRecord->periods()->latest('start_at')->first();
        $defaultStart = $lastPeriod?->end_at?->copy() ?? today();

        return $schema
            ->components([
                DatePicker::make('start_at')
                    ->label('Start date')
                    ->default(fn (): string => $defaultStart->toDateString())
                    ->required()
                    ->rule('date')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: static fn (Unique $rule): Unique => $rule->where('budget_id', $ownerRecord->id)
                    )
                    ->helperText('Transactions on this date are included in the period.'),
                DatePicker::make('end_at')
                    ->label('End date (exclusive)')
                    ->default(fn (): string => $defaultStart->copy()->addMonth()->toDateString())
                    ->required()
                    ->after('start_at')
                    ->rule('date')
                    ->helperText('Transactions on this date belong to the next period.'),
                TextInput::make('amount')
                    ->label('Budgeted Amount')
                    ->default(static fn (): ?string => $lastPeriod?->amount)
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01')
                    ->rule('decimal:0,6'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('range_label')
            ->defaultSort('start_at', 'desc')
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with('aggregates'))
            ->columns([
                TextColumn::make('start_at')
                    ->label('Starts')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Ends (exclusive)')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Budgeted')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('spent_amount')
                    ->label('Spent')
                    ->numeric(2)
                    ->state(static fn (BudgetPeriod $record): string => $record->spent_amount)
                    ->color(static function (BudgetPeriod $record, mixed $state): string {
                        $spent = (float) $state;
                        $amount = (float) $record->amount;

                        if ($spent > $amount) {
                            return 'danger';
                        }

                        if ($spent > $amount * 0.9) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                TextColumn::make('remaining_amount')
                    ->label('Remaining')
                    ->numeric(2)
                    ->state(static fn (BudgetPeriod $record): string => $record->remaining_amount)
                    ->color(static function (mixed $state): string {
                        $remaining = (float) $state;

                        if ($remaining < 0) {
                            return 'danger';
                        }

                        return 'success';
                    }),
                TextColumn::make('usage_percent')
                    ->label('% Used')
                    ->state(static fn (BudgetPeriod $record): string => $record->usage_percent)
                    ->suffix('%')
                    ->color(static function (mixed $state): string {
                        $percent = (float) $state;

                        if ($percent > 100) {
                            return 'danger';
                        }

                        if ($percent > 90) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(static function (BudgetPeriod $record): void {
                        $hasTransactions = DB::table('ledger_transactions')
                            ->where('budget_period_id', $record->id)
                            ->exists();

                        if ($hasTransactions) {
                            throw new RuntimeException("Cannot delete period {$record->range_label} with associated transactions.");
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(static function (iterable $records): void {
                            foreach ($records as $record) {
                                $hasTransactions = DB::table('ledger_transactions')
                                    ->where('budget_period_id', $record->id)
                                    ->exists();

                                if ($hasTransactions) {
                                    throw new RuntimeException("Cannot delete period {$record->range_label} with associated transactions.");
                                }
                            }
                        }),
                ]),
            ]);
    }
}
