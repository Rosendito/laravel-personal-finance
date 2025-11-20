<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Data\AccountBalanceData;
use App\Helpers\MoneyFormatter;
use App\Services\Queries\AccountBalanceQueryService;
use App\Services\Queries\NetWorthQueryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class AccountBalancesWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected int|array|null $columns = 4;

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

        // Calculate net worth
        $netWorth = app(NetWorthQueryService::class)->calculateForUser($user);
        $netWorthStat = Stat::make('Patrimonio Neto', MoneyFormatter::format($netWorth, $currency));

        /** @var Collection<int, AccountBalanceData> $balances */
        $balances = app(AccountBalanceQueryService::class)
            ->totalsForUser($user)
            ->filter(static fn (AccountBalanceData $balance): bool => $balance->is_fundamental === false);

        if ($balances->isEmpty()) {
            return [
                $netWorthStat,
                Stat::make('Balances de cuentas', 'Sin datos'),
            ];
        }

        $accountStats = $balances
            ->map(
                static fn (AccountBalanceData $balance): Stat => Stat::make(
                    $balance->name,
                    MoneyFormatter::format($balance->balance, $balance->currency_code),
                ),
            )
            ->values()
            ->all();

        return [
            // $netWorthStat,
            ...$accountStats,
        ];
    }
}
