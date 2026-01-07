<?php

declare(strict_types=1);

namespace App\Jobs\ExchangeRates;

use App\Actions\ExchangeRates\SyncExchangeRatesAction;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Exceptions\ExchangeRatesException;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncExchangeRatesJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int>  $exchangeCurrencyPairIds
     */
    public function __construct(
        public int $exchangeSourceId,
        public array $exchangeCurrencyPairIds = [],
    ) {}

    public function handle(SyncExchangeRatesAction $syncExchangeRatesAction): void
    {
        $source = ExchangeSource::query()->find($this->exchangeSourceId);

        if (! $source instanceof ExchangeSource) {
            throw ExchangeRatesException::sourceNotFound($this->exchangeSourceId);
        }

        if ($this->exchangeCurrencyPairIds === []) {
            $syncExchangeRatesAction->execute($source);

            return;
        }

        $pairs = ExchangeCurrencyPair::query()
            ->whereIn('id', $this->exchangeCurrencyPairIds)
            ->get();

        if ($pairs->count() !== count($this->exchangeCurrencyPairIds)) {
            throw ExchangeRatesException::pairsNotFound($this->exchangeCurrencyPairIds);
        }

        $syncExchangeRatesAction->execute($source, new RequestedPairsData($pairs));
    }
}
