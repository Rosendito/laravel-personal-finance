<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

final class PricesAndMerchantsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $navigationLabel = 'Precios & Comercios';

    protected static ?int $navigationSort = 999;
}
