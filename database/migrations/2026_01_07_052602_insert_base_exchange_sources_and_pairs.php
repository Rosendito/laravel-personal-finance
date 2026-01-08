<?php

declare(strict_types=1);

use App\Enums\ExchangeSourceKey;
use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Currency::query()->updateOrCreate(
            ['code' => 'EUR'],
            ['precision' => 2],
        );

        $sources = [
            [
                'key' => ExchangeSourceKey::BCV->value,
                'name' => 'Banco Central de Venezuela',
                'type' => 'official',
                'metadata' => [
                    'url' => 'https://www.bcv.org.ve',
                ],
            ],
            [
                'key' => ExchangeSourceKey::BINANCE_P2P->value,
                'name' => 'Binance P2P',
                'type' => 'p2p',
                'metadata' => [
                    'url' => 'https://p2p.binance.com',
                ],
            ],
        ];

        foreach ($sources as $source) {
            ExchangeSource::query()->updateOrCreate(
                ['key' => $source['key']],
                [
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'metadata' => $source['metadata'],
                ],
            );
        }

        $pairs = [
            ['base_currency_code' => 'USD', 'quote_currency_code' => 'VES'],
            ['base_currency_code' => 'EUR', 'quote_currency_code' => 'VES'],
            ['base_currency_code' => 'USDT', 'quote_currency_code' => 'VES'],
        ];

        foreach ($pairs as $pair) {
            ExchangeCurrencyPair::query()->updateOrCreate(
                [
                    'base_currency_code' => $pair['base_currency_code'],
                    'quote_currency_code' => $pair['quote_currency_code'],
                ],
            );
        }
    }

    public function down(): void
    {
        ExchangeSource::query()->whereIn('key', [
            ExchangeSourceKey::BCV->value,
            ExchangeSourceKey::BINANCE_P2P->value,
        ])->delete();

        ExchangeCurrencyPair::query()
            ->whereIn('base_currency_code', ['USD', 'EUR', 'USDT'])
            ->where('quote_currency_code', 'VES')
            ->delete();

        Currency::query()->where('code', 'EUR')->delete();
    }
};
