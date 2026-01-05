<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Enums\LedgerAccountType;
use App\Filament\Resources\LedgerTransactions\Actions\RegisterExpenseFilamentAction;
use App\Filament\Resources\LedgerTransactions\Actions\RegisterIncomeFilamentAction;
use App\Filament\Resources\LedgerTransactions\Actions\TransferFundsFilamentAction;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\LedgerTransactions\Widgets\AccountBalancesWidget;
use App\Filament\Resources\LedgerTransactions\Widgets\BudgetWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListLedgerTransactions extends ListRecords
{
    protected static string $resource = LedgerTransactionResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'expense' => Tab::make('Gastos')
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->whereHas('entries.account', static fn (Builder $q): Builder => $q->where('type', LedgerAccountType::EXPENSE))),
            'income' => Tab::make('Ingresos')
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->whereHas('entries.account', static fn (Builder $q): Builder => $q->where('type', LedgerAccountType::INCOME))),
            'transfer' => Tab::make('Transacciones')
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->whereDoesntHave('entries.account', static fn (Builder $q): Builder => $q->whereIn('type', [LedgerAccountType::INCOME, LedgerAccountType::EXPENSE]))),
            'all' => Tab::make('Todas'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'expense';
    }

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
            BudgetWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
        ];
    }
}
