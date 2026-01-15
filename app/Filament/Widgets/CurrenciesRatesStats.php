<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ExchangeSourceKey;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Throwable;

final class CurrenciesRatesStats extends StatsOverviewWidget
{
    public ?string $error = null;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        try {
            $this->error = null;

            $bcvUsdVes = $this->getLatestRate(ExchangeSourceKey::BCV, 'USD', 'VES');
            $bcvEurVes = $this->getLatestRate(ExchangeSourceKey::BCV, 'EUR', 'VES');
            $binanceUsdtVes = $this->getLatestRate(ExchangeSourceKey::BINANCE_P2P, 'USDT', 'VES');

            return [
                $this->getBcvUsdVesStat($bcvUsdVes),
                $this->getBcvEurVesStat($bcvEurVes),
                $this->getUsdtVesStat($binanceUsdtVes),
                $this->getBcvUsdVsUsdtStat($bcvUsdVes, $binanceUsdtVes),
                $this->getBcvEurVsUsdtStat($bcvEurVes, $binanceUsdtVes),
            ];
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();

            return [
                Stat::make('Error', 'No se pudo obtener datos')
                    ->description($this->error)
                    ->color('danger'),
            ];
        }
    }

    private function getBcvUsdVesStat(?ExchangeRate $rate): Stat
    {
        if (! $rate instanceof ExchangeRate) {
            return Stat::make('BCV (USD/VES)', 'N/A')->color('gray');
        }

        return Stat::make('BCV (USD/VES)', $this->formatRate($rate))
            ->description($this->buildDescription('Tasa oficial (USD/VES)', $rate))
            ->descriptionIcon('heroicon-m-building-library')
            ->color('info');
    }

    private function getBcvEurVesStat(?ExchangeRate $rate): Stat
    {
        if (! $rate instanceof ExchangeRate) {
            return Stat::make('BCV (EUR/VES)', 'N/A')->color('gray');
        }

        return Stat::make('BCV (EUR/VES)', $this->formatRate($rate))
            ->description($this->buildDescription('Tasa oficial (EUR/VES)', $rate))
            ->descriptionIcon('heroicon-m-building-library')
            ->color('info');
    }

    private function getUsdtVesStat(?ExchangeRate $rate): Stat
    {
        if (! $rate instanceof ExchangeRate) {
            return Stat::make('Binance P2P (USDT/VES)', 'N/A')->color('gray');
        }

        return Stat::make('Binance P2P (USDT/VES)', $this->formatRate($rate))
            ->description($this->buildDescription('Tasa P2P (USDT/VES)', $rate))
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('warning');
    }

    private function getBcvUsdVsUsdtStat(?ExchangeRate $bcvUsdVes, ?ExchangeRate $binanceUsdtVes): Stat
    {
        $percentage = $this->percentageOrNull($bcvUsdVes, $binanceUsdtVes);

        if ($percentage === null) {
            return Stat::make('BCV USD como % de USDT', 'N/A')->color('gray');
        }

        return Stat::make('BCV USD como % de USDT', sprintf('%.2f%%', $percentage))
            ->description($this->buildComparisonDescription('Relación BCV (USD/VES) / Binance (USDT/VES)'))
            ->descriptionIcon($percentage < 100 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
            ->color($percentage < 100 ? 'success' : 'danger');
    }

    private function getBcvEurVsUsdtStat(?ExchangeRate $bcvEurVes, ?ExchangeRate $binanceUsdtVes): Stat
    {
        $percentage = $this->percentageOrNull($bcvEurVes, $binanceUsdtVes);

        if ($percentage === null) {
            return Stat::make('BCV EUR como % de USDT', 'N/A')->color('gray');
        }

        return Stat::make('BCV EUR como % de USDT', sprintf('%.2f%%', $percentage))
            ->description($this->buildComparisonDescription('Relación BCV (EUR/VES) / Binance (USDT/VES)'))
            ->descriptionIcon($percentage < 100 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
            ->color($percentage < 100 ? 'success' : 'danger');
    }

    private function getLatestRate(ExchangeSourceKey $sourceKey, string $baseCurrencyCode, string $quoteCurrencyCode): ?ExchangeRate
    {
        $source = ExchangeSource::query()
            ->where('key', $sourceKey->value)
            ->first();

        if (! $source instanceof ExchangeSource) {
            return null;
        }

        $pair = ExchangeCurrencyPair::query()
            ->where('base_currency_code', $baseCurrencyCode)
            ->where('quote_currency_code', $quoteCurrencyCode)
            ->first();

        if (! $pair instanceof ExchangeCurrencyPair) {
            return null;
        }

        return ExchangeRate::query()
            ->where('exchange_source_id', $source->id)
            ->where('exchange_currency_pair_id', $pair->id)
            ->latest('effective_at')
            ->latest('retrieved_at')
            ->first();
    }

    private function percentageOrNull(?ExchangeRate $numeratorRate, ?ExchangeRate $denominatorRate): ?float
    {
        if (! $numeratorRate instanceof ExchangeRate || ! $denominatorRate instanceof ExchangeRate) {
            return null;
        }

        $denominator = (float) $denominatorRate->rate;

        if ($denominator <= 0) {
            return null;
        }

        $numerator = (float) $numeratorRate->rate;

        return ($numerator / $denominator) * 100;
    }

    private function formatRate(ExchangeRate $rate): string
    {
        return number_format((float) $rate->rate, 2, ',', '.');
    }

    private function buildDescription(string $base, ExchangeRate $rate): string
    {
        $updatedAt = $this->updatedAtForRate($rate);

        return "{$base} · {$this->formatUpdatedAt($updatedAt)}";
    }

    private function buildComparisonDescription(string $base): string
    {
        return $base;
    }

    private function updatedAtForRate(ExchangeRate $rate): CarbonInterface
    {
        return $rate->retrieved_at ?? $rate->effective_at;
    }

    private function formatUpdatedAt(CarbonInterface $dateTime): string
    {
        return $dateTime->format('g:ia');
    }
}
