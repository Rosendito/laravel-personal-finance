<?php

declare(strict_types=1);

use App\Data\Dashboard\CashflowSeriesData;
use App\Data\Dashboard\DashboardSnapshotData;
use App\Filament\Widgets\FinanceSnapshotStats;
use App\Models\User;
use App\Services\Queries\DashboardAccountSnapshotQueryService;
use App\Services\Queries\DashboardCashflowSeriesQueryService;
use Carbon\CarbonInterface;

use function Pest\Laravel\actingAs;

describe(FinanceSnapshotStats::class, function (): void {
    it('shows the monthly profit in green when the balance is positive', function (): void {
        $user = User::factory()->create();
        actingAs($user);

        app()->instance(DashboardAccountSnapshotQueryService::class, new class
        {
            public function snapshot(User $user, CarbonInterface $asOf): DashboardSnapshotData
            {
                return new DashboardSnapshotData(
                    liquidity: '1000.00',
                    loanReceivable: '200.00',
                    liabilitiesOwed: '50.00',
                );
            }
        });

        $cashflowService = new class
        {
            public ?string $receivedStart = null;

            public ?string $receivedEnd = null;

            public function dailySeries(User $user, CarbonInterface $startAt, CarbonInterface $endAt): CashflowSeriesData
            {
                $this->receivedStart = $startAt->toDateString();
                $this->receivedEnd = $endAt->toDateString();

                return new CashflowSeriesData(
                    labels: ['2026-03-01', '2026-03-02'],
                    expenses: ['50.00', '25.00'],
                    incomes: ['100.00', '100.00'],
                );
            }
        };

        app()->instance(DashboardCashflowSeriesQueryService::class, $cashflowService);

        $widget = resolve(FinanceSnapshotStats::class);
        $widget->pageFilters = [
            'start_at' => '2026-03-01',
            'end_at' => '2026-03-31',
        ];

        $getStatsMethod = new ReflectionMethod($widget, 'getStats');

        /** @var array<int, \Filament\Widgets\StatsOverviewWidget\Stat> $stats */
        $stats = $getStatsMethod->invoke($widget);
        $profitStat = $stats[3];

        $currency = config('finance.currency.default', 'USD');

        expect($cashflowService->receivedStart)->toBe('2026-03-01')
            ->and($cashflowService->receivedEnd)->toBe('2026-03-31')
            ->and($profitStat->getValue())->toBe("{$currency} 125.00")
            ->and($profitStat->getColor())->toBe('success')
            ->and($profitStat->getDescription())->toBe('Te quedó 62.50% de lo ingresado este mes')
            ->and($profitStat->getDescriptionIcon())->toBe('heroicon-m-arrow-trending-up');
    });

    it('shows the monthly profit in red when the balance is negative', function (): void {
        $user = User::factory()->create();
        actingAs($user);

        app()->instance(DashboardAccountSnapshotQueryService::class, new class
        {
            public function snapshot(User $user, CarbonInterface $asOf): DashboardSnapshotData
            {
                return new DashboardSnapshotData(
                    liquidity: '1000.00',
                    loanReceivable: '200.00',
                    liabilitiesOwed: '50.00',
                );
            }
        });

        app()->instance(DashboardCashflowSeriesQueryService::class, new class
        {
            public function dailySeries(User $user, CarbonInterface $startAt, CarbonInterface $endAt): CashflowSeriesData
            {
                return new CashflowSeriesData(
                    labels: ['2026-03-01'],
                    expenses: ['140.00'],
                    incomes: ['100.00'],
                );
            }
        });

        $widget = resolve(FinanceSnapshotStats::class);
        $widget->pageFilters = [
            'start_at' => '2026-03-01',
            'end_at' => '2026-03-31',
        ];

        $getStatsMethod = new ReflectionMethod($widget, 'getStats');

        /** @var array<int, \Filament\Widgets\StatsOverviewWidget\Stat> $stats */
        $stats = $getStatsMethod->invoke($widget);
        $profitStat = $stats[3];

        $currency = config('finance.currency.default', 'USD');

        expect($profitStat->getValue())->toBe("{$currency} -40.00")
            ->and($profitStat->getColor())->toBe('danger')
            ->and($profitStat->getDescription())->toBe('Gastaste 40.00% más de lo ingresado este mes')
            ->and($profitStat->getDescriptionIcon())->toBe('heroicon-m-arrow-trending-down');
    });
});
