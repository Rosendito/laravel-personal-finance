<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Pages;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditLedgerAccount extends EditRecord
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Eliminar'),
        ];
    }
}
