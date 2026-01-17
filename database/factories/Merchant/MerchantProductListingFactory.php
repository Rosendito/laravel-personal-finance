<?php

declare(strict_types=1);

namespace Database\Factories\Merchant;

use App\Models\Merchant\Merchant;
use App\Models\Merchant\MerchantProductListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantProductListing>
 */
final class MerchantProductListingFactory extends Factory
{
    protected $model = MerchantProductListing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sizeValue = fake()->boolean(60)
            ? (string) fake()->randomFloat(6, 0.1, 500)
            : null;

        return [
            'merchant_id' => Merchant::factory(),
            'merchant_location_id' => null,
            'product_id' => null,
            'external_id' => fake()->optional()->bothify('ext-####'),
            'external_url' => fake()->optional()->url(),
            'title' => fake()->words(3, true),
            'brand_raw' => fake()->optional()->company(),
            'size_value' => $sizeValue,
            'size_unit' => $sizeValue === null ? null : fake()->randomElement(['g', 'kg', 'oz', 'ml', 'l']),
            'pack_quantity' => fake()->boolean(40) ? fake()->numberBetween(1, 12) : null,
            'is_active' => true,
            'last_seen_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'metadata' => fake()->boolean(20) ? ['source' => fake()->word()] : null,
        ];
    }
}
