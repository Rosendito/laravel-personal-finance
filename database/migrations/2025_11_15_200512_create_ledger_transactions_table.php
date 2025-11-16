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
        Schema::create('ledger_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->string('description');
            $table->dateTime('effective_at');
            $table->date('posted_at')->nullable();
            $table->string('reference')->nullable();
            $table->string('source')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'effective_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
