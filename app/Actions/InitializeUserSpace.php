<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class InitializeUserSpace
{
    public function __construct(
        private EnsureFundamentalAccounts $ensureFundamentalAccounts,
    ) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->createFundamentalAccounts($user);
            // Future: Create default categories, budgets, etc.
        });
    }

    private function createFundamentalAccounts(User $user): void
    {
        // Determine default currency from configuration or fallback to USD.
        $currencyCode = config('finance.currency.default', 'USD');

        // Ensure currency exists (it should be seeded)
        throw_unless(Currency::query()->where('code', $currencyCode)->exists(), RuntimeException::class, "Default currency '{$currencyCode}' not found in the system.");

        $this->ensureFundamentalAccounts->execute($user, $currencyCode);
    }
}
