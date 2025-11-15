<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
final class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sign = fake()->randomElement([-1, 1]);
        $amount = fake()->randomFloat(2, 1, 10_000);

        return [
            'transaction_id' => LedgerTransaction::factory(),
            'account_id' => LedgerAccount::factory(),
            'category_id' => null,
            'amount' => $sign * $amount,
            'currency_code' => fn (array $attributes): string => $this->resolveCurrencyCode($attributes['account_id'] ?? null),
            'amount_base' => null,
            'memo' => fake()->optional()->sentence(),
        ];
    }

    public function withCategory(Category|int|null $category): self
    {
        return $this->state(fn (): array => [
            'category_id' => $category instanceof Category ? $category->id : ($category ?? Category::factory()),
        ]);
    }

    private function resolveCurrencyCode(int|string|null $accountId): string
    {
        $account = $accountId === null ? null : LedgerAccount::query()->find($accountId);

        return $account?->currency_code ?? Currency::factory()->create()->code;
    }
}
