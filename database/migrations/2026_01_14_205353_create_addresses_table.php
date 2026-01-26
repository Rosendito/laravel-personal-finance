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
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();

            // Polymorphic column for reuse in Person/User/Clinic/etc.
            $table->morphs('addressable');

            $table->string('country_code', 2);

            $table->string('administrative_area', 100)->nullable();
            $table->string('locality', 150)->nullable();
            $table->string('dependent_locality', 150)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('sorting_code', 32)->nullable();

            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('address_line3', 255)->nullable();

            $table->string('organization', 255)->nullable();
            $table->string('given_name', 100)->nullable();
            $table->string('additional_name', 100)->nullable();
            $table->string('family_name', 100)->nullable();

            $table->string('label', 50)->nullable();
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['country_code', 'administrative_area', 'locality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
