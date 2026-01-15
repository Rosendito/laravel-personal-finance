<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantType;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
 */
final class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'merchant_type' => fake()->randomElement(MerchantType::cases()),
            'base_url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
