<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Helpers\MoneyFormatter;
use App\Models\User;
use App\Services\Queries\DashboardAccountSnapshotQueryService;
use App\Services\Queries\DashboardCashflowSeriesQueryService;
use App\Support\Dates\MonthlyDateRange;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

final class FinanceSnapshotStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'md' => 4,
    ];

    protected static bool $isLazy = false;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $currency = config('finance.currency.default', 'USD');

        $snapshot = resolve(DashboardAccountSnapshotQueryService::class)->snapshot(
            $user,
            CarbonImmutable::today(),
        );

        return [
            Stat::make('Liquidez', MoneyFormatter::format($snapshot->liquidity, $currency)),
            Stat::make('Prestado', MoneyFormatter::format($snapshot->loanReceivable, $currency)),
            Stat::make('Debo', MoneyFormatter::format($snapshot->liabilitiesOwed, $currency)),
            $this->makeMonthlyProfitStat($user, $currency),
        ];
    }

    private function makeMonthlyProfitStat(User $user, string $currency): Stat
    {
        [$start, $end] = $this->dateRange();

        $series = resolve(DashboardCashflowSeriesQueryService::class)
            ->dailySeries($user, $start, $end);

        $totalIncome = $this->sumSeries($series->incomes);
        $totalExpense = $this->sumSeries($series->expenses);
        $profit = bcsub($totalIncome, $totalExpense, 6);
        $color = $this->profitColor($profit);

        return Stat::make('Profit del mes', MoneyFormatter::format($profit, $currency))
            ->description($this->profitDescription($profit, $totalIncome))
            ->descriptionIcon($this->profitDescriptionIcon($profit))
            ->descriptionColor($color)
            ->color($color);
    }

    /**
     * @param  array<int, float|int|string>  $series
     */
    private function sumSeries(array $series): string
    {
        return array_reduce(
            $series,
            static fn (string $carry, float|int|string $value): string => bcadd($carry, (string) $value, 6),
            '0',
        );
    }

    private function profitColor(string $profit): string
    {
        return match (bccomp($profit, '0', 6)) {
            1 => 'success',
            -1 => 'danger',
            default => 'gray',
        };
    }

    private function profitDescription(string $profit, string $totalIncome): string
    {
        if (bccomp($profit, '0', 6) === 0) {
            return 'Terminaste el mes en equilibrio';
        }

        if (bccomp($totalIncome, '0', 6) <= 0) {
            return bccomp($profit, '0', 6) < 0
                ? 'Sin ingresos registrados y el balance quedó en rojo'
                : 'Sin ingresos registrados en el periodo';
        }

        $percentage = number_format((abs((float) $profit) / (float) $totalIncome) * 100, 2);

        return bccomp($profit, '0', 6) > 0
            ? "Te quedó {$percentage}% de lo ingresado este mes"
            : "Gastaste {$percentage}% más de lo ingresado este mes";
    }

    private function profitDescriptionIcon(string $profit): ?string
    {
        return match (bccomp($profit, '0', 6)) {
            1 => 'heroicon-m-arrow-trending-up',
            -1 => 'heroicon-m-arrow-trending-down',
            default => null,
        };
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
