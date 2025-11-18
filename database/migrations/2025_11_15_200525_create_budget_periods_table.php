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
            $table->date('start_at');
            $table->date('end_at');
            $table->decimal('amount', 20, 6);
            $table->timestamps();

            $table->unique(['budget_id', 'start_at']);
            $table->index(['budget_id', 'start_at', 'end_at']);
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
