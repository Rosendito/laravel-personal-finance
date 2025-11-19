<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Data\AccountBalanceData;
use App\Services\Queries\AccountBalanceQueryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

use function sprintf;

final class AccountBalancesWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

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

        /** @var Collection<int, AccountBalanceData> $balances */
        $balances = app(AccountBalanceQueryService::class)
            ->totalsForUser($user)
            ->filter(static fn (AccountBalanceData $balance): bool => $balance->is_fundamental === false);

        if ($balances->isEmpty()) {
            return [
                Stat::make('Balances de cuentas', 'Sin datos'),
            ];
        }

        return $balances
            ->map(
                fn (AccountBalanceData $balance): Stat => Stat::make(
                    $balance->name,
                    $this->formatBalance($balance->balance, $balance->currency_code),
                ),
            )
            ->values()
            ->all();
    }

    private function formatBalance(int|float|string $amount, string $currency): string
    {
        return sprintf(
            '%s %s',
            $currency,
            number_format((float) $amount, 2, '.', ','),
        );
    }
}
