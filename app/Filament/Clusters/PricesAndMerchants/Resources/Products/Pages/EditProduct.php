<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages;

use App\Filament\Clusters\PricesAndMerchants\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->label('Eliminar'),
        ];
    }
}
