<?php

declare(strict_types=1);

namespace App\Console\Commands\Tests;

use App\Actions\Currencies\FetchBcvRateAction;
use App\Actions\Currencies\FetchUsdtVesRateAction;
use Illuminate\Console\Command;
use Throwable;

final class CompareBcvBinanceFromCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:compare-bcv-binance-from-commands {--trans-amount=10000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use existing BCV and Binance P2P commands to compare rates and show BCV as % of P2P';

    /**
     * Execute the console command.
     */
    public function handle(
        FetchBcvRateAction $fetchBcvRateAction,
        FetchUsdtVesRateAction $fetchUsdtVesRateAction,
    ): int {
        $this->info('Fetching BCV and Binance P2P rates...');

        try {
            $bcvRateData = $fetchBcvRateAction->execute();
            $binanceRateData = $fetchUsdtVesRateAction->execute(transAmount: $this->option('trans-amount'));

            $bcvRate = $bcvRateData->averagePrice;
            $binanceMaxPrice = $binanceRateData->maxPrice;

            if ($binanceMaxPrice <= 0.0) {
                $this->error('Invalid Binance P2P max price: '.$binanceMaxPrice);

                return self::FAILURE;
            }

            $percentage = ($bcvRate / $binanceMaxPrice) * 100;

            $this->line('');
            $this->info('--- Parsed values ---');
            $this->info('BCV rate: '.number_format($bcvRate, 3, '.', '').' VES per USD');
            $this->info('Binance P2P max price: '.number_format($binanceMaxPrice, 3, '.', '').' VES per USDT');
            $this->info('Binance P2P min price: '.number_format($binanceRateData->minPrice, 3, '.', '').' VES per USDT');
            $this->info('Binance P2P average: '.number_format($binanceRateData->averagePrice, 3, '.', '').' VES per USDT');
            $this->info('Binance P2P count: '.$binanceRateData->count.' ads');

            $this->line('');
            $this->info('--- Comparison ---');
            $this->info('BCV as % of P2P (max): '.number_format($percentage, 2, '.', '').'%');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Error: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
