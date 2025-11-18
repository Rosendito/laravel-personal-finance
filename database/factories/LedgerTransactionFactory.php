<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerTransaction>
 */
final class LedgerTransactionFactory extends Factory
{
    protected $model = LedgerTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $effectiveAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'user_id' => User::factory(),
            'account_id' => LedgerAccount::factory(),
            'budget_period_id' => null,
            'description' => fake()->sentence(),
            'effective_at' => $effectiveAt,
            'posted_at' => fake()->boolean(70) ? $effectiveAt->format('Y-m-d') : null,
            'reference' => fake()->optional()->bothify('REF-####-??'),
            'source' => fake()->randomElement(['manual', 'import']),
            'idempotency_key' => fake()->optional()->uuid(),
        ];
    }
}
