<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table): void {
            $table
                ->foreignId('category_id')
                ->nullable()
                ->after('budget_period_id')
                ->constrained()
                ->nullOnDelete();
        });

        // Migrate existing data: take category_id from the first entry of each transaction (by id)
        $entries = DB::table('ledger_entries')
            ->select('transaction_id', 'category_id')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->get();

        $seenTransactionIds = [];

        foreach ($entries as $entry) {
            if (isset($seenTransactionIds[$entry->transaction_id])) {
                continue;
            }

            DB::table('ledger_transactions')
                ->where('id', $entry->transaction_id)
                ->update([
                    'category_id' => $entry->category_id,
                ]);

            $seenTransactionIds[$entry->transaction_id] = true;
        }

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'transaction_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table
                ->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });

        // Copy category_id from transaction back into all its entries
        $rows = DB::table('ledger_entries as e')
            ->join('ledger_transactions as t', 'e.transaction_id', '=', 't.id')
            ->whereNotNull('t.category_id')
            ->select('e.id', 't.category_id')
            ->orderBy('e.id')
            ->get();

        foreach ($rows as $row) {
            DB::table('ledger_entries')
                ->where('id', $row->id)
                ->update([
                    'category_id' => $row->category_id,
                ]);
        }

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->index(['category_id', 'transaction_id']);
        });

        Schema::table('ledger_transactions', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
