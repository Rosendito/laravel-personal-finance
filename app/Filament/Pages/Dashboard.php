<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\LedgerTransactions\Widgets\BudgetWidget;
use App\Filament\Widgets\CashflowTrendChart;
use App\Filament\Widgets\FinanceSnapshotStats;
use App\Filament\Widgets\RecentTransactionsTable;
use App\Filament\Widgets\SpendingByCategoryChart;
use App\Support\Dates\MonthlyDateRange;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

final class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        [$defaultStart, $defaultEnd] = $this->defaultRange();

        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('start_at')
                        ->label('Inicio')
                        ->default($defaultStart->toDateString())
                        ->native(false)
                        ->closeOnDateSelection(),
                    DatePicker::make('end_at')
                        ->label('Fin')
                        ->default($defaultEnd->toDateString())
                        ->native(false)
                        ->closeOnDateSelection(),
                    Actions::make([
                        Action::make('previous_month')
                            ->label('Mes anterior')
                            ->color('gray')
                            ->action(function (Get $get, Set $set): void {
                                $month = $this->selectedMonthStart($get)->subMonth();
                                $this->setMonthRange($set, $month);
                            }),
                        Action::make('current_month')
                            ->label('Mes actual')
                            ->color('gray')
                            ->action(function (Set $set): void {
                                $this->setMonthRange($set, $this->nowMonthStart());
                            })
                            ->disabled(fn (Get $get): bool => $this->selectedMonthStart($get)->equalTo($this->nowMonthStart())),
                        Action::make('next_month')
                            ->label('Mes siguiente')
                            ->color('gray')
                            ->action(function (Get $get, Set $set): void {
                                $month = $this->selectedMonthStart($get)->addMonth();
                                $this->setMonthRange($set, $month);
                            })
                            ->disabled(function (Get $get): bool {
                                $nextMonth = $this->selectedMonthStart($get)->addMonth();

                                return $nextMonth->greaterThan($this->nowMonthStart());
                            }),
                    ])
                        ->label('Acciones')
                        ->columnSpan([
                            'md' => 2,
                        ]),
                ])
                ->columns([
                    'md' => 4,
                ]),
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceSnapshotStats::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SpendingByCategoryChart::class,
            CashflowTrendChart::class,
            BudgetWidget::class,
            RecentTransactionsTable::class,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function defaultRange(): array
    {
        return MonthlyDateRange::forDate(CarbonImmutable::today());
    }

    private function nowMonthStart(): CarbonImmutable
    {
        return CarbonImmutable::today()->startOfMonth();
    }

    private function selectedMonthStart(Get $get): CarbonImmutable
    {
        $start = $get->string('start_at', isNullable: true);

        if ($start !== null) {
            return CarbonImmutable::parse($start)->startOfMonth();
        }

        $end = $get->string('end_at', isNullable: true);

        if ($end !== null) {
            return CarbonImmutable::parse($end)->startOfMonth();
        }

        return $this->nowMonthStart();
    }

    private function setMonthRange(Set $set, CarbonImmutable $monthStart): void
    {
        $monthStart = $monthStart->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $set('start_at', $monthStart->toDateString(), false, true);
        $set('end_at', $monthEnd->toDateString(), false, true);
    }
}
