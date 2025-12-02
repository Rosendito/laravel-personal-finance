<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Enums\LedgerAccountSubType;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\LedgerTransactions\Tables\LiabilitiesTransactionsTable;
use App\Filament\Resources\LedgerTransactions\Widgets\DebtLoanBalancesWidget;
use Filament\Actions\Action;
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
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $this->configureTableQuery($query);
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('register_debt')
                ->label('Registrar Deuda')
                ->color('danger'),

            Action::make('register_loan')
                ->label('Prestar Dinero')
                ->color('warning'),
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
        return $query->whereHas('entries.account', function (Builder $query): Builder {
            return $query->whereIn('subtype', [
                LedgerAccountSubType::LOAN_PAYABLE,
                LedgerAccountSubType::LOAN_RECEIVABLE,
            ]);
        });
    }
}
