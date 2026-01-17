<?php

declare(strict_types=1);

namespace Database\Factories\Merchant;

use App\Models\Merchant\Merchant;
use App\Models\Merchant\MerchantLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantLocation>
 */
final class MerchantLocationFactory extends Factory
{
    protected $model = MerchantLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => sprintf('%s %s', fake()->company(), fake()->city()),
        ];
    }
}
