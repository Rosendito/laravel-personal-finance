<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeCurrencyPair>
 */
final class ExchangeCurrencyPairFactory extends Factory
{
    protected $model = ExchangeCurrencyPair::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'base_currency_code' => Currency::factory(),
            'quote_currency_code' => Currency::factory(),
        ];
    }
}
