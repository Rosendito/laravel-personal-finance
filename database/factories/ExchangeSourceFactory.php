<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExchangeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeSource>
 */
final class ExchangeSourceFactory extends Factory
{
    protected $model = ExchangeSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->company(),
            'type' => fake()->randomElement(['official', 'p2p', 'market', 'manual']),
            'metadata' => fake()->boolean(30) ? ['url' => fake()->url()] : null,
        ];
    }
}
