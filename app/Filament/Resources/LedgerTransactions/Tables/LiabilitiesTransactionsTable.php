<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Tables;

use App\Filament\Resources\LedgerTransactions\Actions\EditLedgerTransactionFilamentAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

use function sprintf;

final class LiabilitiesTransactionsTable
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
            ->columns(LedgerTransactionsTable::getColumns())
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
}
