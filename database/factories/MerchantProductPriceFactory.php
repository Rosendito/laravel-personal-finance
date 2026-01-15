<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntryMethod;
use App\Models\MerchantProductListing;
use App\Models\MerchantProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantProductPrice>
 */
final class MerchantProductPriceFactory extends Factory
{
    protected $model = MerchantProductPrice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priceRegular = fake()->boolean(35)
            ? (string) fake()->randomFloat(6, 0.5, 200)
            : null;

        return [
            'merchant_product_listing_id' => MerchantProductListing::factory(),
            'observed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'entry_method' => fake()->randomElement(EntryMethod::cases()),
            'currency' => fake()->currencyCode(),
            'price_regular' => $priceRegular,
            'price_current' => (string) fake()->randomFloat(6, 0.5, 200),
            'is_promo' => false,
            'promo_type' => fake()->optional()->word(),
            'promo_description' => fake()->optional()->sentence(),
            'tax_included' => fake()->optional()->boolean(),
            'stock_status' => fake()->optional()->randomElement(['in_stock', 'out_of_stock', 'limited']),
            'raw_payload' => fake()->boolean(20) ? ['source' => fake()->word()] : null,
        ];
    }
}
