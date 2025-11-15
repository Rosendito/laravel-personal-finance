<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

final class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'precision' => 2],
            ['code' => 'EUR', 'precision' => 2],
            ['code' => 'MXN', 'precision' => 2],
            ['code' => 'JPY', 'precision' => 0],
            ['code' => 'BTC', 'precision' => 8],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                ['precision' => $currency['precision']]
            );
        }
    }
}
