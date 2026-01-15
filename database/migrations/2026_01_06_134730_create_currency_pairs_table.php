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
        Schema::create('exchange_currency_pairs', function (Blueprint $table): void {
            $table->id();

            $table->char('base_currency_code', 3);
            $table->char('quote_currency_code', 3);

            $table->timestamps();

            $table->unique(['base_currency_code', 'quote_currency_code']);

            $table->foreign('base_currency_code')
                ->references('code')
                ->on('currencies')
                ->cascadeOnDelete();

            $table->foreign('quote_currency_code')
                ->references('code')
                ->on('currencies')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_currency_pairs');
    }
};
