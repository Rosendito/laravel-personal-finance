<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Pages;

use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Enums\LedgerAccountSubType;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;

class LiabilitiesTransactionsPage extends ListRecords
{
    protected static string $resource = LedgerTransactionResource::class;

    protected ?string $heading = 'Gestión de Deudas y Préstamos';

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

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $this->configureTableQuery($query);
            });
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
