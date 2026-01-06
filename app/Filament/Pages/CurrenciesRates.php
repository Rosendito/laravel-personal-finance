<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Currencies\FetchBcvRateAction;
use App\Actions\Currencies\FetchUsdtVesRateAction;
use App\Data\Currencies\ExchangeRateData;
use App\Filament\Widgets\CurrenciesRatesStats;
use BackedEnum;
use Filament\Pages\Page;
use Throwable;

final class CurrenciesRates extends Page
{
    public int|float $transAmount = 10000;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Tasas de Cambio';

    protected static ?string $title = 'Tasas de Cambio';

    protected string $view = 'filament.pages.currencies-rates';

    /**
     * @return array{bcv: ExchangeRateData|null, binance: ExchangeRateData|null, transAmount: int|float, error: string|null}
     */
    public function getWidgetData(): array
    {
        try {
            $bcvData = resolve(FetchBcvRateAction::class)->execute();
        } catch (Throwable $e) {
            return [
                'bcv' => null,
                'binance' => null,
                'transAmount' => $this->transAmount,
                'error' => 'BCV: '.$e->getMessage(),
            ];
        }

        try {
            $binanceData = resolve(FetchUsdtVesRateAction::class)->execute(transAmount: $this->transAmount, rows: 20);
        } catch (Throwable $e) {
            return [
                'bcv' => $bcvData,
                'binance' => null,
                'transAmount' => $this->transAmount,
                'error' => 'Binance: '.$e->getMessage(),
            ];
        }

        return [
            'bcv' => $bcvData,
            'binance' => $binanceData,
            'transAmount' => $this->transAmount,
            'error' => null,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CurrenciesRatesStats::class,
        ];
    }
}
