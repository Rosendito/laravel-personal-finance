<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
final class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => sprintf('Budget %s', fake()->monthName()),
            'is_active' => true,
        ];
    }
}
