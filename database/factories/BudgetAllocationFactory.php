<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Category;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetAllocation>
 */
final class BudgetAllocationFactory extends Factory
{
    protected $model = BudgetAllocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'category_id' => fn (array $attributes): int => $this->resolveCategoryId($attributes['budget_id'] ?? null),
            'amount' => fake()->randomFloat(2, 100, 5_000),
            'currency_code' => Currency::factory(),
        ];
    }

    public function forBudget(Budget $budget): self
    {
        return $this->state(fn (): array => [
            'budget_id' => $budget->id,
        ]);
    }

    public function forCategory(Category $category): self
    {
        return $this->state(fn (): array => [
            'category_id' => $category->id,
        ]);
    }

    private function resolveCategoryId(int|string|null $budgetId): int
    {
        $budget = $budgetId === null ? null : Budget::query()->find($budgetId);
        $userId = $budget?->user_id ?? User::factory()->create()->id;

        return Category::factory()
            ->expense()
            ->state(fn (): array => [
                'user_id' => $userId,
            ])
            ->create()
            ->id;
    }
}
