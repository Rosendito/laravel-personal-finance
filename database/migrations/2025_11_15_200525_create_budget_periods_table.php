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
        Schema::create('budget_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('amount', 20, 6);
            $table->string('currency_code', 3);
            $table->timestamps();

            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->unique(['budget_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_periods');
    }
};
