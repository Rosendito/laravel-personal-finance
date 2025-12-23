<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Helpers\MoneyFormatter;
use App\Services\Queries\DashboardAccountSnapshotQueryService;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class FinanceSnapshotStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'md' => 3,
    ];

    protected static bool $isLazy = false;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $endDate = $this->endDate();
        $currency = config('finance.currency.default', 'USD');

        $snapshot = app(DashboardAccountSnapshotQueryService::class)->snapshot(
            $user,
            $endDate,
        );

        return [
            Stat::make('Liquidez', MoneyFormatter::format($snapshot->liquidity, $currency)),
            Stat::make('Prestado', MoneyFormatter::format($snapshot->loanReceivable, $currency)),
            Stat::make('Debo', MoneyFormatter::format($snapshot->liabilitiesOwed, $currency)),
        ];
    }

    private function endDate(): CarbonImmutable
    {
        $end = $this->pageFilters['end_at'] ?? null;

        if (is_string($end) && $end !== '') {
            return CarbonImmutable::parse($end);
        }

        return CarbonImmutable::today();
    }
}
