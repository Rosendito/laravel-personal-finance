<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts\Pages;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLedgerAccount extends CreateRecord
{
    protected static string $resource = LedgerAccountResource::class;
}
