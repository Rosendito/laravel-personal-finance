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
        Schema::create('cached_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->morphs('aggregatable');
            $table->string('key');
            $table->string('scope')->nullable();
            $table->decimal('value_decimal', 20, 6)->nullable();
            $table->bigInteger('value_int')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index(
                ['aggregatable_type', 'aggregatable_id', 'key', 'scope'],
                'cached_agg_lookup'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cached_aggregates');
    }
};
