<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Pages;

use App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\MerchantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

final class ManageMerchants extends ManageRecords
{
    protected static string $resource = MerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo comercio')
                ->modalHeading('Crear comercio'),
        ];
    }
}
