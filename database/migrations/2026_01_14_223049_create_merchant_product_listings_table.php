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
        Schema::create('merchant_product_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')
                ->constrained('merchant_merchants')
                ->cascadeOnDelete();
            $table->foreignId('merchant_location_id')
                ->nullable()
                ->constrained('merchant_locations')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('external_url')->nullable();
            $table->string('title');
            $table->string('brand_raw')->nullable();
            $table->decimal('size_value', 20, 6)->nullable();
            $table->string('size_unit')->nullable();
            $table->unsignedInteger('pack_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_product_listings');
    }
};
