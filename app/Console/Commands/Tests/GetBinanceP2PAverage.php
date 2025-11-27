<?php

declare(strict_types=1);

namespace App\Console\Commands\Tests;

use App\Actions\Currencies\FetchUsdtVesRateAction;
use Illuminate\Console\Command;
use Throwable;

final class GetBinanceP2PAverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:get-binance-p2p-average';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Binance P2P ads and compute the average USDT/VES price for a given amount';

    /**
     * Execute the console command.
     */
    public function handle(FetchUsdtVesRateAction $fetchUsdtVesRateAction): int
    {
        $this->info('Requesting Binance P2P API...');

        try {
            $exchangeRateData = $fetchUsdtVesRateAction->execute();

            $this->info('Prices found: '.implode(', ', $exchangeRateData->prices));
            $this->info('Count: '.$exchangeRateData->count.' ads');
            $this->info('Min price: '.number_format($exchangeRateData->minPrice, 3, '.', '').' VES per USDT');
            $this->info('Max price: '.number_format($exchangeRateData->maxPrice, 3, '.', '').' VES per USDT');
            $this->info('Average price: '.number_format($exchangeRateData->averagePrice, 3, '.', '').' VES per USDT');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Error: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
