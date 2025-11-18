<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Pages;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva cuenta')
                ->modalHeading('Crear cuenta'),
        ];
    }
}
