<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Data\AccountBalanceData;
use App\Enums\LedgerAccountSubType;
use App\Filament\Resources\LedgerTransactions\Actions\CollectLoanFilamentAction;
use App\Filament\Resources\LedgerTransactions\Actions\PayDebtFilamentAction;
use App\Helpers\MoneyFormatter;
use App\Services\Queries\AccountBalanceQueryService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class DebtLoanBalancesWidget extends StatsOverviewWidget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.resources.ledger-transactions.widgets.debt-loan-balances-widget';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected int|array|null $columns = 4;

    public function collectLoan(): Action
    {
        return CollectLoanFilamentAction::make();
    }

    public function payDebt(): Action
    {
        return PayDebtFilamentAction::make();
    }

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
        $allBalances = resolve(AccountBalanceQueryService::class)
            ->totalsForUser($user)
            ->filter(static function (AccountBalanceData $balance): bool {
                if ($balance->is_fundamental) {
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

        if ($receivables->isNotEmpty()) {
            $receivablesStats = $receivables
                ->map(
                    fn (AccountBalanceData $balance): Stat => DebtLoanStat::make(
                        $balance->name,
                        MoneyFormatter::format($balance->balance, $balance->currency_code),
                    )
                        ->color('success')
                        ->icon('heroicon-m-arrow-down-circle')
                        ->accountId($balance->account_id)
                        ->actionName('collectLoan'),
                )
                ->values()
                ->all();

            $stats = [...$stats, ...$receivablesStats];
        }

        if ($payables->isNotEmpty()) {
            $payablesStats = $payables
                ->map(
                    fn (AccountBalanceData $balance): Stat => DebtLoanStat::make(
                        $balance->name,
                        MoneyFormatter::format($balance->balance, $balance->currency_code),
                    )
                        ->color('danger')
                        ->icon('heroicon-m-arrow-up-circle')
                        ->accountId($balance->account_id)
                        ->actionName('payDebt'),
                )
                ->values()
                ->all();

            $stats = [...$stats, ...$payablesStats];
        }

        if ($stats === []) {
            return [
                Stat::make('Préstamos y Deudas', 'Sin datos')
                    ->description('No hay cuentas de préstamos o deudas registradas')
                    ->color('gray'),
            ];
        }

        return $stats;
    }
}
