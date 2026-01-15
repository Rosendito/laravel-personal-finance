<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'addressable_id' => User::factory(),
            'addressable_type' => User::class,
            'country_code' => mb_strtoupper(fake()->countryCode()),
            'administrative_area' => fake()->stateAbbr(),
            'locality' => fake()->city(),
            'dependent_locality' => fake()->streetSuffix(),
            'postal_code' => fake()->postcode(),
            'sorting_code' => fake()->regexify('[A-Z0-9]{4}'),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'address_line3' => null,
            'organization' => fake()->company(),
            'given_name' => fake()->firstName(),
            'additional_name' => fake()->optional()->firstName(),
            'family_name' => fake()->lastName(),
            'label' => fake()->randomElement(['home', 'work', 'billing', 'shipping']),
            'is_default' => fake()->boolean(20),
        ];
    }
}
