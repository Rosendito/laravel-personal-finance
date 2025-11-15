<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CachedAggregateKey;
use App\Models\Budget;
use App\Models\CachedAggregate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<CachedAggregate>
 */
final class CachedAggregateFactory extends Factory
{
    protected $model = CachedAggregate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'aggregatable_type' => Budget::class,
            'aggregatable_id' => Budget::factory(),
            'key' => CachedAggregateKey::CurrentBalance->value,
            'scope' => null,
            'value_decimal' => fake()->randomFloat(4, -10_000, 10_000),
            'value_int' => fake()->numberBetween(-10_000, 10_000),
            'value_json' => [],
        ];
    }

    public function forAggregatable(Model $aggregatable): self
    {
        return $this->state(fn (): array => [
            'aggregatable_type' => $aggregatable::class,
            'aggregatable_id' => $aggregatable->getKey(),
        ]);
    }

    public function withKey(CachedAggregateKey $key): self
    {
        return $this->state(fn (): array => [
            'key' => $key->value,
        ]);
    }
}
