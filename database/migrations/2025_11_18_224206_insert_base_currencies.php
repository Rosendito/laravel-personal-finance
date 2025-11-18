<?php

declare(strict_types=1);

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $currencies = [
            ['code' => 'USD', 'precision' => 2],
            ['code' => 'VES', 'precision' => 2],
            ['code' => 'USDT', 'precision' => 2],
            ['code' => 'BTC', 'precision' => 8],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                ['precision' => $currency['precision']]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Currency::query()->whereIn('code', ['USD', 'VES', 'USDT', 'BTC'])->delete();
    }
};
