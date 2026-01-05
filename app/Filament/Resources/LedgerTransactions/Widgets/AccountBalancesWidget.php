<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Data\AccountBalanceData;
use App\Enums\LedgerAccountSubType;
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
        $netWorth = resolve(NetWorthQueryService::class)->calculateForUser($user);
        $netWorthStat = Stat::make('Patrimonio Neto', MoneyFormatter::format($netWorth, $currency));

        $liquidSubtypes = array_map(
            static fn (LedgerAccountSubType $subtype): string => $subtype->value,
            LedgerAccountSubType::liquidSubtypes()
        );

        /** @var Collection<int, AccountBalanceData> $balances */
        $balances = resolve(AccountBalanceQueryService::class)
            ->totalsForUser($user)
            ->filter(static function (AccountBalanceData $balance) use ($liquidSubtypes): bool {
                if ($balance->is_fundamental) {
                    return false;
                }

                if ($balance->subtype === null) {
                    return false;
                }

                return in_array($balance->subtype, $liquidSubtypes, true);
            });

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
