<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LedgerAccountSubType;
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
        $type = fake()->randomElement(LedgerAccountType::cases());
        $subtypes = $type->subtypes();
        $subtype = ! empty($subtypes) ? fake()->randomElement($subtypes) : null;

        return [
            'user_id' => User::factory(),
            'name' => sprintf('%s Account', fake()->company()),
            'type' => $type->value,
            'subtype' => $subtype?->value,
            'currency_code' => Currency::factory(),
            'is_archived' => false,
        ];
    }

    public function ofType(LedgerAccountType $type): self
    {
        return $this->state(fn (): array => [
            'type' => $type->value,
            'subtype' => null,
        ]);
    }

    public function withSubtype(LedgerAccountSubType $subtype): self
    {
        return $this->state(fn (): array => [
            'type' => $subtype->type()->value,
            'subtype' => $subtype->value,
        ]);
    }
}
