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
        Schema::create('merchant_product_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_product_listing_id')
                ->constrained('merchant_product_listings')
                ->cascadeOnDelete();
            $table->dateTime('observed_at');
            $table->string('entry_method', 20);
            $table->string('currency', 3);
            $table->decimal('price_regular', 20, 6)->nullable();
            $table->decimal('price_current', 20, 6);
            $table->boolean('is_promo')->default(false);
            $table->string('promo_type')->nullable();
            $table->string('promo_description')->nullable();
            $table->boolean('tax_included')->nullable();
            $table->string('stock_status')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['merchant_product_listing_id', 'observed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_product_prices');
    }
};
