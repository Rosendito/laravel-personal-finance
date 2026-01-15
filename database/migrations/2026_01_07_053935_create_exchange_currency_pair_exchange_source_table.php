<?php

declare(strict_types=1);

use App\Enums\ExchangeSourceKey;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_currency_pair_exchange_source', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('exchange_currency_pair_id')
                ->constrained('exchange_currency_pairs')
                ->cascadeOnDelete();

            $table->foreignId('exchange_source_id')
                ->constrained('exchange_sources')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['exchange_currency_pair_id', 'exchange_source_id']);
            $table->index(['exchange_source_id', 'exchange_currency_pair_id']);
        });

        $this->insertBaseMappings();
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_currency_pair_exchange_source');
    }

    private function insertBaseMappings(): void
    {
        $sourceIdByKey = ExchangeSource::query()
            ->whereIn('key', [
                ExchangeSourceKey::BCV->value,
                ExchangeSourceKey::BINANCE_P2P->value,
            ])
            ->pluck('id', 'key');

        $pairIdByKey = ExchangeCurrencyPair::query()
            ->whereIn('base_currency_code', ['USD', 'EUR', 'USDT'])
            ->where('quote_currency_code', 'VES')
            ->get()
            ->mapWithKeys(fn (ExchangeCurrencyPair $pair): array => [
                "{$pair->base_currency_code}/{$pair->quote_currency_code}" => $pair->id,
            ]);

        $mappings = [
            [ExchangeSourceKey::BCV->value, 'USD/VES'],
            [ExchangeSourceKey::BCV->value, 'EUR/VES'],
            [ExchangeSourceKey::BINANCE_P2P->value, 'USDT/VES'],
        ];

        foreach ($mappings as [$sourceKey, $pairKey]) {
            $sourceId = $sourceIdByKey[$sourceKey] ?? null;
            $pairId = $pairIdByKey[$pairKey] ?? null;
            if (! is_int($sourceId)) {
                continue;
            }
            if (! is_int($pairId)) {
                continue;
            }

            DB::table('exchange_currency_pair_exchange_source')->updateOrInsert(
                [
                    'exchange_currency_pair_id' => $pairId,
                    'exchange_source_id' => $sourceId,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
};
