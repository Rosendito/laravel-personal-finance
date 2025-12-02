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
use Illuminate\Support\Carbon;

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
