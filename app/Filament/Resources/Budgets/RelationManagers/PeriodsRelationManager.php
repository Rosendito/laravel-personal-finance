<?php

declare(strict_types=1);

namespace App\Filament\Resources\Budgets\RelationManagers;

use App\Enums\CategoryType;
use App\Models\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use RuntimeException;

final class PeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';

    public function form(Schema $schema): Schema
    {
        $ownerRecord = $this->getOwnerRecord();
        $lastPeriod = $ownerRecord->periods()->latest('period')->first();

        return $schema
            ->components([
                TextInput::make('period')
                    ->label('Period (YYYY-MM)')
                    ->placeholder('2025-11')
                    ->default(static fn (): string => now()->format('Y-m'))
                    ->required()
                    ->rule('string')
                    ->maxLength(7)
                    ->regex('/^\d{4}-\d{2}$/')
                    ->helperText('Format: YYYY-MM (e.g., 2025-11)')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: static function (Unique $rule) use ($ownerRecord): Unique {
                            return $rule->where('budget_id', $ownerRecord->id);
                        }
                    ),
                TextInput::make('amount')
                    ->label('Budgeted Amount')
                    ->default(static fn (): ?string => $lastPeriod?->amount)
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01')
                    ->rule('decimal:0,6'),
                Select::make('currency_code')
                    ->label('Currency')
                    ->default(static fn (): ?string => $lastPeriod?->currency_code ?? Currency::query()->first()?->code)
                    ->required()
                    ->options(Currency::query()->pluck('code', 'code'))
                    ->searchable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period')
            ->defaultSort('period', 'desc')
            ->columns([
                TextColumn::make('period')
                    ->label('Period')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Budgeted')
                    ->money(static fn (Model $record): string => $record->currency_code)
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->badge()
                    ->sortable(),
                TextColumn::make('spent')
                    ->label('Spent')
                    ->money(static fn (Model $record): string => $record->currency_code)
                    ->state(static function (Model $record): string {
                        $transactionMonthExpression = match (DB::connection()->getDriverName()) {
                            'sqlite' => "strftime('%Y-%m', t.effective_at)",
                            'pgsql' => "TO_CHAR(t.effective_at, 'YYYY-MM')",
                            default => "DATE_FORMAT(t.effective_at, '%Y-%m')",
                        };

                        $spent = DB::query()
                            ->from('ledger_entries as e')
                            ->selectRaw('COALESCE(SUM(e.amount), 0) as total')
                            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
                            ->join('categories as c', 'c.id', '=', 'e.category_id')
                            ->where('t.budget_id', $record->budget_id)
                            ->whereRaw("{$transactionMonthExpression} = ?", [$record->period])
                            ->where('c.type', CategoryType::Expense->value)
                            ->first();

                        return bcadd((string) ($spent?->total ?? '0'), '0', 6);
                    })
                    ->color(static function (Model $record, $state): string {
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
                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->money(static fn (Model $record): string => $record->currency_code)
                    ->state(static function (Model $record): string {
                        $transactionMonthExpression = match (DB::connection()->getDriverName()) {
                            'sqlite' => "strftime('%Y-%m', t.effective_at)",
                            'pgsql' => "TO_CHAR(t.effective_at, 'YYYY-MM')",
                            default => "DATE_FORMAT(t.effective_at, '%Y-%m')",
                        };

                        $spent = DB::query()
                            ->from('ledger_entries as e')
                            ->selectRaw('COALESCE(SUM(e.amount), 0) as total')
                            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
                            ->join('categories as c', 'c.id', '=', 'e.category_id')
                            ->where('t.budget_id', $record->budget_id)
                            ->whereRaw("{$transactionMonthExpression} = ?", [$record->period])
                            ->where('c.type', CategoryType::Expense->value)
                            ->first();

                        $spentAmount = (string) ($spent?->total ?? '0');

                        return bcsub($record->amount, $spentAmount, 6);
                    })
                    ->color(static function ($state): string {
                        $remaining = (float) $state;

                        if ($remaining < 0) {
                            return 'danger';
                        }

                        return 'success';
                    }),
                TextColumn::make('usage_percent')
                    ->label('% Used')
                    ->state(static function (Model $record): string {
                        $transactionMonthExpression = match (DB::connection()->getDriverName()) {
                            'sqlite' => "strftime('%Y-%m', t.effective_at)",
                            'pgsql' => "TO_CHAR(t.effective_at, 'YYYY-MM')",
                            default => "DATE_FORMAT(t.effective_at, '%Y-%m')",
                        };

                        $spent = DB::query()
                            ->from('ledger_entries as e')
                            ->selectRaw('COALESCE(SUM(e.amount), 0) as total')
                            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
                            ->join('categories as c', 'c.id', '=', 'e.category_id')
                            ->where('t.budget_id', $record->budget_id)
                            ->whereRaw("{$transactionMonthExpression} = ?", [$record->period])
                            ->where('c.type', CategoryType::Expense->value)
                            ->first();

                        $spentAmount = (float) ($spent?->total ?? '0');
                        $budgetAmount = (float) $record->amount;

                        if ($budgetAmount === 0.0) {
                            return '0';
                        }

                        return number_format(($spentAmount / $budgetAmount) * 100, 2);
                    })
                    ->suffix('%')
                    ->color(static function ($state): string {
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
                    ->before(static function (Model $record): void {
                        $hasTransactions = DB::table('ledger_transactions')
                            ->where('budget_id', $record->budget_id)
                            ->whereRaw(
                                match (DB::connection()->getDriverName()) {
                                    'sqlite' => "strftime('%Y-%m', effective_at) = ?",
                                    'pgsql' => "TO_CHAR(effective_at, 'YYYY-MM') = ?",
                                    default => "DATE_FORMAT(effective_at, '%Y-%m') = ?",
                                },
                                [$record->period]
                            )
                            ->exists();

                        if ($hasTransactions) {
                            throw new RuntimeException('Cannot delete a period with associated transactions.');
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(static function ($records): void {
                            foreach ($records as $record) {
                                $hasTransactions = DB::table('ledger_transactions')
                                    ->where('budget_id', $record->budget_id)
                                    ->whereRaw(
                                        match (DB::connection()->getDriverName()) {
                                            'sqlite' => "strftime('%Y-%m', effective_at) = ?",
                                            'pgsql' => "TO_CHAR(effective_at, 'YYYY-MM') = ?",
                                            default => "DATE_FORMAT(effective_at, '%Y-%m') = ?",
                                        },
                                        [$record->period]
                                    )
                                    ->exists();

                                if ($hasTransactions) {
                                    throw new RuntimeException("Cannot delete period {$record->period} with associated transactions.");
                                }
                            }
                        }),
                ]),
            ]);
    }
}
