<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

final class BudgetStat extends Stat
{
    protected string $view = 'filament.resources.ledger-transactions.widgets.budget-stat';

    public static function fromBudget(
        string $name,
        string $spent,
        string $total,
        string $remaining,
        float $percentage,
    ): static {
        /** @var static $stat */
        $stat = parent::make($name, $remaining);

        $color = match (true) {
            $percentage > 90 => 'danger',
            $percentage > 70 => 'warning',
            default => 'success',
        };

        return $stat
            ->color($color)
            ->viewData([
                'spentLabel' => $spent,
                'totalLabel' => $total,
                'remainingLabel' => $remaining,
                'percentage' => $percentage,
            ]);
    }
}
