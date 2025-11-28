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
        Schema::table('ledger_accounts', function (Blueprint $table): void {
            $table->string('subtype', 20)->nullable()->after('type')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table): void {
            $table->dropColumn('subtype');
        });
    }
};
