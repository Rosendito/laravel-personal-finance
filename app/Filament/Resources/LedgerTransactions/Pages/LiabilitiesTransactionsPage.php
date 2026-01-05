<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Enums\LedgerAccountSubType;
use App\Filament\Resources\LedgerTransactions\Actions\RegisterBorrowingFilamentAction;
use App\Filament\Resources\LedgerTransactions\Actions\RegisterLendingFilamentAction;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\LedgerTransactions\Tables\LiabilitiesTransactionsTable;
use App\Filament\Resources\LedgerTransactions\Widgets\DebtLoanBalancesWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class LiabilitiesTransactionsPage extends ListRecords
{
    protected static string $resource = LedgerTransactionResource::class;

    protected ?string $heading = 'Gestión de Deudas y Préstamos';

    public function table(Table $table): Table
    {
        return LiabilitiesTransactionsTable::configure($table)
            ->modifyQueryUsing($this->configureTableQuery(...));
    }

    protected function getHeaderActions(): array
    {
        return [
            RegisterBorrowingFilamentAction::make(),
            RegisterLendingFilamentAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DebtLoanBalancesWidget::class,
        ];
    }

    private function configureTableQuery(Builder $query): Builder
    {
        return $query->whereHas('entries.account', fn (Builder $query): Builder => $query->whereIn('subtype', [
            LedgerAccountSubType::LOAN_PAYABLE,
            LedgerAccountSubType::LOAN_RECEIVABLE,
        ]));
    }
}
