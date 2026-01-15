<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\Support\Htmlable;

final class DebtLoanStat extends Stat
{
    protected string $view = 'filament.resources.ledger-transactions.widgets.debt-loan-stat';

    private ?string $accountId = null;

    private ?string $actionName = null;

    public static function make(string|Htmlable $label, mixed $value): static
    {
        return new self($label, $value);
    }

    public function accountId(string|int $accountId): static
    {
        $this->accountId = (string) $accountId;

        return $this;
    }

    public function actionName(string $actionName): static
    {
        $this->actionName = $actionName;

        return $this;
    }

    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    public function getActionName(): ?string
    {
        return $this->actionName;
    }
}
