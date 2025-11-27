<?php

declare(strict_types=1);

namespace App\Console\Commands\Tests;

use App\Actions\Currencies\FetchBcvRateAction;
use Illuminate\Console\Command;
use Throwable;

final class GetBcvRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:get-bcv-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the BCV rate for the current date';

    /**
     * Execute the console command.
     */
    public function handle(FetchBcvRateAction $fetchBcvRateAction): int
    {
        $this->info('Fetching BCV rate...');

        try {
            $exchangeRateData = $fetchBcvRateAction->execute();

            $this->info('BCV Rate: '.number_format($exchangeRateData->averagePrice, 2, ',', '.'));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
