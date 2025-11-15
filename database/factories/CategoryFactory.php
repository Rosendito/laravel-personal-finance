<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(array_map(static fn (CategoryType $type): string => $type->value, CategoryType::cases())),
            'is_archived' => false,
        ];
    }

    public function income(): self
    {
        return $this->state(fn (): array => [
            'type' => CategoryType::Income->value,
        ]);
    }

    public function expense(): self
    {
        return $this->state(fn (): array => [
            'type' => CategoryType::Expense->value,
        ]);
    }
}
