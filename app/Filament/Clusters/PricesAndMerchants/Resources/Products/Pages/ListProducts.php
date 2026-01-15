<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages;

use App\Filament\Clusters\PricesAndMerchants\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo producto')
                ->modalHeading('Crear producto'),
        ];
    }
}
