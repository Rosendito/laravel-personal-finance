<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sizeValue = fake()->boolean(50)
            ? (string) fake()->randomFloat(6, 0.1, 500)
            : null;

        return [
            'name' => fake()->words(3, true),
            'brand' => fake()->optional()->company(),
            'category' => fake()->optional()->word(),
            'canonical_size_value' => $sizeValue,
            'canonical_size_unit' => $sizeValue === null ? null : fake()->randomElement(['g', 'kg', 'oz', 'ml', 'l']),
            'barcode' => fake()->boolean(60) ? fake()->unique()->ean13() : null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
