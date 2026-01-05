<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Tables;

use App\Filament\Resources\LedgerTransactions\Actions\EditLedgerTransactionFilamentAction;
use App\Helpers\MoneyFormatter;
use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function sprintf;

final class LedgerTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                static fn (Builder $query): Builder => $query
                    ->withAmountSummary()
                    ->withBaseAmountSummary()
                    ->with([
                        'entries.account',
                        'category',
                        'budgetPeriod.budget',
                    ]),
            )
            ->defaultSort('effective_at', 'desc')
            ->columns(self::getColumns())
            ->filters([
                Filter::make('category')
                    ->label('Categoría')
                    ->schema([
                        Toggle::make('uncategorized')
                            ->label('Sin categoría')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(static function (bool $state, callable $set): void {
                                if ($state) {
                                    $set('category_ids', []);
                                }
                            }),
                        Select::make('category_ids')
                            ->label('Categoría')
                            ->multiple()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(static function (): array {
                                $userId = Auth::id() ?? 0;

                                return Category::query()
                                    ->where('user_id', $userId)
                                    ->where('is_archived', false)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->disabled(static fn (callable $get): bool => (bool) $get('uncategorized'))
                            ->afterStateUpdated(static function (array $state, callable $set): void {
                                if ($state !== []) {
                                    $set('uncategorized', false);
                                }
                            }),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        $uncategorized = (bool) ($data['uncategorized'] ?? false);
                        /** @var array<int, int|string> $categoryIds */
                        $categoryIds = (array) ($data['category_ids'] ?? []);

                        return $query->whereCategoryFilter($uncategorized, $categoryIds);
                    })
                    ->indicateUsing(static function (array $data): ?string {
                        if (($data['uncategorized'] ?? false) === true) {
                            return 'Sin categoría';
                        }

                        $count = is_array($data['category_ids'] ?? null) ? count($data['category_ids']) : 0;

                        return $count > 0 ? sprintf('%s categoría(s)', (string) $count) : null;
                    }),
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
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        return $query
                            ->when(
                                filled($from),
                                static fn (Builder $query): Builder => $query->where('effective_at', '>=', Date::parse($from)->startOfDay()),
                            )
                            ->when(
                                filled($until),
                                static fn (Builder $query): Builder => $query->where('effective_at', '<=', Date::parse($until)->endOfDay()),
                            );
                    })
                    ->indicateUsing(static function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from !== null && $until !== null) {
                            return sprintf(
                                'Entre %s y %s',
                                Date::parse($from)->toFormattedDateString(),
                                Date::parse($until)->toFormattedDateString(),
                            );
                        }

                        if ($from !== null) {
                            return sprintf('Desde %s', Date::parse($from)->toFormattedDateString());
                        }

                        if ($until !== null) {
                            return sprintf('Hasta %s', Date::parse($until)->toFormattedDateString());
                        }

                        return null;
                    })
                    ->columns(2)
                    ->columnSpan(2),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->recordActions([
                EditLedgerTransactionFilamentAction::make(),
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<int, TextColumn>
     */
    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('description')
                ->label('Descripción')
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(50),
            TextColumn::make('amount_summary')
                ->label('Monto')
                ->state(static function (LedgerTransaction $record): ?string {
                    $amount = $record->amount_summary;
                    $currency = $record->amount_currency;

                    if ($amount === null) {
                        return null;
                    }

                    return MoneyFormatter::format($amount, $currency ?? '');
                })
                ->placeholder('—')
                ->alignRight()
                ->sortable()
                ->toggleable(),
            TextColumn::make('amount_base_summary')
                ->label('Monto base')
                ->state(static function (LedgerTransaction $record): ?string {
                    $amount = $record->amount_base_summary;
                    $defaultCurrency = config('finance.currency.default');

                    if ($amount === null) {
                        return null;
                    }

                    return MoneyFormatter::format($amount, $defaultCurrency);
                })
                ->placeholder('—')
                ->alignRight()
                ->sortable()
                ->toggleable(),
            TextColumn::make('category_summary')
                ->label('Categoría')
                ->state(static fn (LedgerTransaction $record): ?string => $record->category?->name)
                ->placeholder('—')
                ->limit(30)
                ->wrap()
                ->toggleable(isToggledHiddenByDefault: static fn (Page $livewire): bool => $livewire->activeTab === 'transfer'),
            TextColumn::make('from_accounts')
                ->label('Desde')
                ->state(static fn (LedgerTransaction $record): ?string => self::summarizeAccounts($record, outgoing: true))
                ->placeholder('—')
                ->limit(30)
                ->wrap()
                ->toggleable()
                ->hidden(static fn (Page $livewire): bool => $livewire->activeTab === 'income'),
            TextColumn::make('to_accounts')
                ->label('Hacia')
                ->state(static fn (LedgerTransaction $record): ?string => self::summarizeAccounts($record, outgoing: false))
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
        ];
    }

    private static function summarizeAccounts(LedgerTransaction $record, bool $outgoing): ?string
    {
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
}
