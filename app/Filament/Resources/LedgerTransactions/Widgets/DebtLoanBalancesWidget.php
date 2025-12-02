<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Data\AccountBalanceData;
use App\Enums\LedgerAccountSubType;
use App\Helpers\MoneyFormatter;
use App\Services\Queries\AccountBalanceQueryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class DebtLoanBalancesWidget extends StatsOverviewWidget
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

        /** @var Collection<int, AccountBalanceData> $allBalances */
        $allBalances = app(AccountBalanceQueryService::class)
            ->totalsForUser($user)
            ->filter(static function (AccountBalanceData $balance): bool {
                if ($balance->is_fundamental === true) {
                    return false;
                }

                if ($balance->subtype === null) {
                    return false;
                }

                return in_array(
                    $balance->subtype,
                    [
                        LedgerAccountSubType::LOAN_RECEIVABLE->value,
                        LedgerAccountSubType::LOAN_PAYABLE->value,
                    ],
                    true,
                );
            });

        $receivables = $allBalances->filter(
            static fn (AccountBalanceData $balance): bool => $balance->subtype === LedgerAccountSubType::LOAN_RECEIVABLE->value,
        );

        $payables = $allBalances->filter(
            static fn (AccountBalanceData $balance): bool => $balance->subtype === LedgerAccountSubType::LOAN_PAYABLE->value,
        );

        $stats = [];

        // Section: Por Cobrar (Receivables)
        if ($receivables->isNotEmpty()) {
            $receivablesStats = $receivables
                ->map(
                    static function (AccountBalanceData $balance): Stat {
                        return Stat::make(
                            $balance->name,
                            MoneyFormatter::format($balance->balance, $balance->currency_code),
                        )
                            ->description('Botón: Cobrar')
                            ->color('success')
                            ->icon('heroicon-m-arrow-down-circle');
                    },
                )
                ->values()
                ->all();

            $stats = [...$stats, ...$receivablesStats];
        }

        // Section: Por Pagar (Payables)
        if ($payables->isNotEmpty()) {
            $payablesStats = $payables
                ->map(
                    static function (AccountBalanceData $balance): Stat {
                        return Stat::make(
                            $balance->name,
                            MoneyFormatter::format($balance->balance, $balance->currency_code),
                        )
                            ->description('Botón: Pagar')
                            ->color('danger')
                            ->icon('heroicon-m-arrow-up-circle');
                    },
                )
                ->values()
                ->all();

            $stats = [...$stats, ...$payablesStats];
        }

        if (empty($stats)) {
            return [
                Stat::make('Préstamos y Deudas', 'Sin datos')
                    ->description('No hay cuentas de préstamos o deudas registradas')
                    ->color('gray'),
            ];
        }

        return $stats;
    }
}
