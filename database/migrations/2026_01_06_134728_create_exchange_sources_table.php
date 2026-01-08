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
        Schema::create('exchange_sources', function (Blueprint $table): void {
            $table->id();

            $table->string('key')->unique(); // bcv, binance_p2p, paralelo
            $table->string('name'); // Banco Central de Venezuela
            $table->string('type'); // official, p2p, market, manual

            $table->json('metadata')->nullable(); // url, market, payment_method, etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_sources');
    }
};
