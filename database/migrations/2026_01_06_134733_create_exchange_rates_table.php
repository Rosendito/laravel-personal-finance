<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('exchange_currency_pair_id')
                ->constrained('exchange_currency_pairs')
                ->cascadeOnDelete();

            $table->foreignId('exchange_source_id')
                ->constrained('exchange_sources')
                ->cascadeOnDelete();

            $table->decimal('rate', 36, 18); // 1 base = X quote

            $table->timestamp('effective_at'); // fecha contable real
            $table->timestamp('retrieved_at')->nullable();

            $table->boolean('is_estimated')->default(false);

            $table->json('meta')->nullable(); // bid/ask, sample size, notes

            $table->timestamps();

            $table->index([
                'exchange_currency_pair_id',
                'exchange_source_id',
                'effective_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
