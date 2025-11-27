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
    protected $signature = 'tests:compare-bcv-binance-from-commands';

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
            $binanceRateData = $fetchUsdtVesRateAction->execute();

            $bcvRate = $bcvRateData->averagePrice;
            $binanceAverage = $binanceRateData->averagePrice;

            if ($binanceAverage <= 0.0) {
                $this->error('Invalid Binance P2P average: '.$binanceAverage);

                return self::FAILURE;
            }

            $percentage = ($bcvRate / $binanceAverage) * 100;

            $this->line('');
            $this->info('--- Parsed values ---');
            $this->info('BCV rate: '.number_format($bcvRate, 3, '.', '').' VES per USD');
            $this->info('Binance P2P average: '.number_format($binanceAverage, 3, '.', '').' VES per USDT');

            $this->line('');
            $this->info('--- Comparison ---');
            $this->info('BCV as % of P2P: '.number_format($percentage, 2, '.', '').'%');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Error: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
