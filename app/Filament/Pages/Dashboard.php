<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\LedgerTransactions\Widgets\BudgetWidget;
use App\Filament\Widgets\CashflowTrendChart;
use App\Filament\Widgets\FinanceSnapshotStats;
use App\Filament\Widgets\RecentTransactionsTable;
use App\Filament\Widgets\SpendingByCategoryChart;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        [$defaultStart, $defaultEnd] = $this->defaultRange();

        return $schema->components([
            Section::make('Rango de fechas')
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
                ])
                ->columns([
                    'md' => 2,
                ]),
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceSnapshotStats::class,
            SpendingByCategoryChart::class,
            CashflowTrendChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            BudgetWidget::class,
            RecentTransactionsTable::class,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function defaultRange(): array
    {
        $today = CarbonImmutable::today();
        $fifteenth = $today->day < 15
            ? $today->subMonth()->setDay(15)
            : $today->setDay(15);

        $end = $today->day < 15
            ? $today->setDay(15)
            : $today->addMonth()->setDay(15);

        return [$fifteenth, $end];
    }
}
