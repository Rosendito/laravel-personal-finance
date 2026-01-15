<?php

declare(strict_types=1);

use App\Actions\EnsureFundamentalAccounts;
use App\Actions\ResolveFundamentalAccount;
use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\User;

describe(ResolveFundamentalAccount::class, function (): void {
    it('returns the fundamental account for the given user/currency/type', function (): void {
        $currencyCode = config('finance.currency.default', 'USD');

        $user = User::factory()->create();

        $ensureFundamentalAccounts = resolve(EnsureFundamentalAccounts::class);
        $ensureFundamentalAccounts->execute($user, $currencyCode);

        $action = new ResolveFundamentalAccount($ensureFundamentalAccounts);

        $actual = $action->execute($user, $currencyCode, LedgerAccountType::EXPENSE);

        $expected = LedgerAccount::query()
            ->where('user_id', $user->id)
            ->where('currency_code', $currencyCode)
            ->where('type', LedgerAccountType::EXPENSE)
            ->where('is_fundamental', true)
            ->firstOrFail();

        expect($actual->is($expected))->toBeTrue();
    });

    it('throws when the fundamental account does not exist', function (): void {
        $currencyCode = config('finance.currency.default', 'USD');

        $user = User::factory()->create();

        $ensureFundamentalAccounts = resolve(EnsureFundamentalAccounts::class);

        $action = new ResolveFundamentalAccount($ensureFundamentalAccounts);

        $call = fn (): LedgerAccount => $action->execute($user, $currencyCode, LedgerAccountType::EQUITY);

        expect($call)->toThrow(
            LedgerIntegrityException::class,
            sprintf(
                'Fundamental %s account for user %d and currency %s was not found.',
                LedgerAccountType::EQUITY->name,
                $user->id,
                $currencyCode,
            ),
        );
    });
});
