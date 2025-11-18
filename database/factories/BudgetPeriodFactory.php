<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
        $start = CarbonImmutable::createFromMutable(fake()->dateTimeBetween('-3 months', '+3 months'))
            ->startOfDay();
        $end = $start->addDays(fake()->numberBetween(7, 45));

        return [
            'budget_id' => Budget::factory(),
            'start_at' => $start->toDateString(),
            'end_at' => $end->toDateString(),
            'amount' => fake()->randomFloat(2, 50, 5_000),
        ];
    }

    public function startingAt(CarbonInterface|string $start, CarbonInterface|string|null $end = null): self
    {
        $startDate = $this->resolveDate($start);
        $endDate = $end === null ? $startDate->addMonth() : $this->resolveDate($end);

        if ($endDate->lessThanOrEqualTo($startDate)) {
            $endDate = $startDate->addDay();
        }

        return $this->state(fn (): array => [
            'start_at' => $startDate->toDateString(),
            'end_at' => $endDate->toDateString(),
        ]);
    }

    private function resolveDate(CarbonInterface|string $value): CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }
}
