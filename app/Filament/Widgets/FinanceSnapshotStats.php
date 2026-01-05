<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Helpers\MoneyFormatter;
use App\Services\Queries\DashboardAccountSnapshotQueryService;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class FinanceSnapshotStats extends StatsOverviewWidget
{
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

        $currency = config('finance.currency.default', 'USD');

        $snapshot = resolve(DashboardAccountSnapshotQueryService::class)->snapshot(
            $user,
            CarbonImmutable::today(),
        );

        return [
            Stat::make('Liquidez', MoneyFormatter::format($snapshot->liquidity, $currency)),
            Stat::make('Prestado', MoneyFormatter::format($snapshot->loanReceivable, $currency)),
            Stat::make('Debo', MoneyFormatter::format($snapshot->liabilitiesOwed, $currency)),
        ];
    }
}
