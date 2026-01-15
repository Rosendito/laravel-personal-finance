<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages;

use App\Filament\Clusters\PricesAndMerchants\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
