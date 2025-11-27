<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Data\Currencies\ExchangeRateData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CurrenciesRatesStats extends StatsOverviewWidget
{
    public ?ExchangeRateData $bcv = null;

    public ?ExchangeRateData $binance = null;

    public int|float $transAmount = 10000;

    public ?string $error = null;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        if ($this->error !== null) {
            return [
                Stat::make('Error', 'No se pudo obtener datos')
                    ->description($this->error)
                    ->color('danger'),
            ];
        }

        return [
            $this->getBcvStat(),
            $this->getUsdtVesStat(),
            $this->getBcvPercentageStat(),
        ];
    }

    private function getBcvStat(): Stat
    {
        if ($this->bcv === null) {
            return Stat::make('BCV (USD/VES)', 'N/A')
                ->color('gray');
        }

        return Stat::make('BCV (USD/VES)', number_format($this->bcv->averagePrice, 2, ',', '.'))
            ->description('Tasa oficial del Banco Central')
            ->descriptionIcon('heroicon-m-building-library')
            ->color('info');
    }

    private function getUsdtVesStat(): Stat
    {
        if ($this->binance === null) {
            return Stat::make('Binance P2P (USDT/VES)', 'N/A')
                ->color('gray');
        }

        return Stat::make('Binance P2P (USDT/VES)', number_format($this->binance->maxPrice, 2, ',', '.'))
            ->description(sprintf('%s VES | Min: %s | Avg: %s', number_format($this->transAmount, 0, ',', '.'), number_format($this->binance->minPrice, 2, ',', '.'), number_format($this->binance->averagePrice, 2, ',', '.')))
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('warning');
    }

    private function getBcvPercentageStat(): Stat
    {
        if ($this->bcv === null || $this->binance === null) {
            return Stat::make('BCV como % de P2P', 'N/A')
                ->color('gray');
        }

        if ($this->binance->maxPrice <= 0) {
            return Stat::make('BCV como % de P2P', 'N/A')
                ->color('gray');
        }

        $percentage = ($this->bcv->averagePrice / $this->binance->maxPrice) * 100;

        return Stat::make('BCV como % de P2P', sprintf('%.2f%%', $percentage))
            ->description('Relación BCV / Binance')
            ->descriptionIcon($percentage < 100 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
            ->color($percentage < 100 ? 'success' : 'danger');
    }
}
