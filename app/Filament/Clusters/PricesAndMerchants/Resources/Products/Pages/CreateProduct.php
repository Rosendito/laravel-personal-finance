<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages;

use App\Filament\Clusters\PricesAndMerchants\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
