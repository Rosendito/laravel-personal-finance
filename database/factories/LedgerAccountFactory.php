<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LedgerAccountType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerAccount>
 */
final class LedgerAccountFactory extends Factory
{
    protected $model = LedgerAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => sprintf('%s Account', fake()->company()),
            'type' => fake()->randomElement(array_map(static fn (LedgerAccountType $type): string => $type->value, LedgerAccountType::cases())),
            'currency_code' => Currency::factory(),
            'is_archived' => false,
        ];
    }

    public function ofType(LedgerAccountType $type): self
    {
        return $this->state(fn (): array => [
            'type' => $type->value,
        ]);
    }
}
