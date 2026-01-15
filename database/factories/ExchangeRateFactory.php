<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
final class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exchange_currency_pair_id' => ExchangeCurrencyPair::factory(),
            'exchange_source_id' => ExchangeSource::factory(),
            'rate' => (string) fake()->randomFloat(6, 0.000001, 1_000_000),
            'effective_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'retrieved_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'is_estimated' => fake()->boolean(10),
            'meta' => fake()->boolean(30) ? ['notes' => fake()->sentence()] : null,
        ];
    }
}
