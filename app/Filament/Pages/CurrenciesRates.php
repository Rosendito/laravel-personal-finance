<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\CurrenciesRatesStats;
use BackedEnum;
use Filament\Pages\Page;

final class CurrenciesRates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Tasas de Cambio';

    protected static ?string $title = 'Tasas de Cambio';

    protected string $view = 'filament.pages.currencies-rates';

    protected function getHeaderWidgets(): array
    {
        return [
            CurrenciesRatesStats::class,
        ];
    }
}
