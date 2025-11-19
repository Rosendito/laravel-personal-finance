<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Filament\Resources\LedgerTransactions\Actions\RegisterExpenseFilamentAction;
use App\Filament\Resources\LedgerTransactions\Actions\RegisterIncomeFilamentAction;
use App\Filament\Resources\LedgerTransactions\Actions\TransferFundsFilamentAction;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\LedgerTransactions\Widgets\AccountBalancesWidget;
use Filament\Resources\Pages\ListRecords;

final class ListLedgerTransactions extends ListRecords
{
    protected static string $resource = LedgerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RegisterIncomeFilamentAction::make(),
            RegisterExpenseFilamentAction::make(),
            TransferFundsFilamentAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccountBalancesWidget::class,
        ];
    }
}
