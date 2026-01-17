<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Queries\DashboardCashflowSeriesQueryService;
use App\Support\Dates\MonthlyDateRange;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

final class CashflowTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Flujo (Ingresos vs Gastos)';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        [$start, $end] = $this->dateRange();

        $series = resolve(DashboardCashflowSeriesQueryService::class)
            ->dailySeries($user, $start, $end);

        return [
            'datasets' => [
                [
                    'label' => 'Gastos',
                    'data' => array_map(static fn (float|int|string $value): float => (float) $value, $series->expenses),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef4444',
                    'fill' => false,
                ],
                [
                    'label' => 'Ingresos',
                    'data' => array_map(static fn (float|int|string $value): float => (float) $value, $series->incomes),
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => false,
                ],
            ],
            'labels' => $series->labels,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(): array
    {
        $start = $this->pageFilters['start_at'] ?? null;
        $end = $this->pageFilters['end_at'] ?? null;

        $startDate = is_string($start) && $start !== ''
            ? CarbonImmutable::parse($start)
            : null;

        $endDate = is_string($end) && $end !== ''
            ? CarbonImmutable::parse($end)
            : null;

        if (! $startDate instanceof CarbonImmutable && ! $endDate instanceof CarbonImmutable) {
            return MonthlyDateRange::forDate(CarbonImmutable::today());
        }

        $startDate ??= $endDate->startOfMonth();
        $endDate ??= $startDate->endOfMonth();

        if ($endDate->lessThan($startDate)) {
            $endDate = $startDate->endOfMonth();
        }

        return [$startDate, $endDate];
    }
}
