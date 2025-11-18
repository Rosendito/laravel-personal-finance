<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetPeriod>
 */
final class BudgetPeriodFactory extends Factory
{
    protected $model = BudgetPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $period = fake()->dateTimeBetween('-3 months', '+3 months')->format('Y-m');

        return [
            'budget_id' => Budget::factory(),
            'period' => $period,
            'amount' => fake()->randomFloat(2, 50, 5_000),
            'currency_code' => Currency::factory(),
        ];
    }

    public function forPeriod(string $period): self
    {
        return $this->state(fn (): array => [
            'period' => $period,
        ]);
    }
}
