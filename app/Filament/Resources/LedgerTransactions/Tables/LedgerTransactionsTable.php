<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Tables;

use App\Models\BudgetPeriod;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

use function sprintf;

final class LedgerTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                static fn (Builder $query): Builder => $query->with([
                    'entries.account',
                    'entries.category',
                    'budgetPeriod.budget',
                ]),
            )
            ->defaultSort('effective_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('amount_summary')
                    ->label('Monto')
                    ->state(static fn (Model $record): ?string => self::summarizeAmount($record))
                    ->placeholder('—')
                    ->alignRight()
                    ->toggleable(),
                TextColumn::make('category_summary')
                    ->label('Categoría')
                    ->state(static fn (Model $record): ?string => self::summarizeCategories($record))
                    ->placeholder('—')
                    ->limit(30)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: static fn (Page $livewire): bool => $livewire->activeTab === 'transfer'),
                TextColumn::make('from_accounts')
                    ->label('Desde')
                    ->state(static fn (Model $record): ?string => self::summarizeAccounts($record, outgoing: true))
                    ->placeholder('—')
                    ->limit(30)
                    ->wrap()
                    ->toggleable()
                    ->hidden(static fn (Page $livewire): bool => $livewire->activeTab === 'income'),
                TextColumn::make('to_accounts')
                    ->label('Hacia')
                    ->state(static fn (Model $record): ?string => self::summarizeAccounts($record, outgoing: false))
                    ->placeholder('—')
                    ->limit(30)
                    ->wrap()
                    ->toggleable()
                    ->hidden(static fn (Page $livewire): bool => ($livewire->activeTab ?? 'expense') === 'expense'),
                TextColumn::make('effective_at')
                    ->label('Fecha efectiva')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('posted_at')
                    ->label('Fecha publicación')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('budgetPeriod.budget.name')
                    ->label('Presupuesto')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable()
                    ->hidden(static fn (Page $livewire): bool => $livewire->activeTab !== 'expense'),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('effective_at')
                    ->label('Fecha efectiva')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                static fn (Builder $query, string $date): Builder => $query->whereDate('effective_at', '>=', $date),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                static fn (Builder $query, string $date): Builder => $query->whereDate('effective_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(static function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from !== null && $until !== null) {
                            return sprintf(
                                'Entre %s y %s',
                                Carbon::parse($from)->toFormattedDateString(),
                                Carbon::parse($until)->toFormattedDateString(),
                            );
                        }

                        if ($from !== null) {
                            return sprintf('Desde %s', Carbon::parse($from)->toFormattedDateString());
                        }

                        if ($until !== null) {
                            return sprintf('Hasta %s', Carbon::parse($until)->toFormattedDateString());
                        }

                        return null;
                    }),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options([
                        'manual' => 'Manual',
                        'import' => 'Importación',
                        'api' => 'API',
                    ]),
                SelectFilter::make('budget_period_id')
                    ->label('Período presupuestario')
                    ->relationship(
                        name: 'budgetPeriod',
                        titleAttribute: 'id',
                        modifyQueryUsing: static function (Builder $query): Builder {
                            $userId = Auth::id() ?? 0;

                            return $query->whereHas('budget', static function (Builder $q) use ($userId): Builder {
                                return $q->where('user_id', $userId);
                            });
                        },
                    )
                    ->getOptionLabelFromRecordUsing(
                        static fn (Model $record): string => $record instanceof BudgetPeriod ? $record->range_label : '',
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    private static function summarizeAmount(Model $record): ?string
    {
        if (! $record instanceof LedgerTransaction) {
            return null;
        }

        $largest = null;
        $currency = null;

        foreach ($record->entries as $entry) {
            $absolute = self::absoluteAmount((string) $entry->amount);

            if ($largest === null || bccomp($absolute, $largest, 6) === 1) {
                $largest = $absolute;
                $currency = $entry->currency_code;
            }
        }

        if ($largest === null) {
            return null;
        }

        return sprintf(
            '%s %s',
            $currency ?? '',
            number_format((float) $largest, 2, '.', ','),
        );
    }

    private static function summarizeCategories(Model $record): ?string
    {
        if (! $record instanceof LedgerTransaction) {
            return null;
        }

        $categories = $record->entries
            ->map(static fn (LedgerEntry $entry): ?string => $entry->category?->name)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        return $categories !== '' ? $categories : null;
    }

    private static function summarizeAccounts(Model $record, bool $outgoing): ?string
    {
        if (! $record instanceof LedgerTransaction) {
            return null;
        }

        $accounts = $record->entries
            ->filter(static function (LedgerEntry $entry) use ($outgoing): bool {
                $comparison = bccomp((string) $entry->amount, '0', 6);

                return $outgoing ? $comparison === -1 : $comparison === 1;
            })
            ->map(static fn (LedgerEntry $entry): string => $entry->account->name ?? '—')
            ->unique()
            ->values()
            ->implode(', ');

        if ($accounts === '' && $record->entries->isNotEmpty()) {
            $accounts = $record->entries
                ->map(static fn (LedgerEntry $entry): string => $entry->account->name ?? '—')
                ->unique()
                ->values()
                ->implode(', ');
        }

        return $accounts !== '' ? $accounts : null;
    }

    private static function absoluteAmount(string $amount): string
    {
        $normalized = mb_ltrim($amount, '+');

        if (str_starts_with($normalized, '-')) {
            return mb_ltrim(mb_substr($normalized, 1), '+') ?: '0';
        }

        return $normalized;
    }
}
