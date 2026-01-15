<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
final class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = null;

        for ($attempts = 0; $attempts < 25; $attempts++) {
            $candidate = mb_strtoupper((string) fake()->currencyCode());

            if (! Currency::query()->where('code', $candidate)->exists()) {
                $code = $candidate;
                break;
            }
        }

        $code ??= 'ZZZ';

        return [
            'code' => $code,
            'precision' => fake()->numberBetween(0, 4),
        ];
    }
}
