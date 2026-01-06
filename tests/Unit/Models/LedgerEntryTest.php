<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Date;

describe(LedgerEntry::class, function (): void {
    /**
     * @return array{
     *   currency: Currency,
     *   user: User,
     *   transaction: LedgerTransaction,
     *   assetAccount: LedgerAccount
     * }
     */
    $makeContext = function (): array {
        $currency = Currency::query()->where('code', 'USD')->firstOrFail();
        $user = User::factory()->create();

        $transaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'description' => 'Integrity Test',
                'effective_at' => Date::now(),
                'posted_at' => Date::now()->toDateString(),
            ])
            ->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Checking',
            ])
            ->create();

        return [
            'currency' => $currency,
            'user' => $user,
            'transaction' => $transaction,
            'assetAccount' => $assetAccount,
        ];
    };

    it('rejects zero amount entries', function () use ($makeContext): void {
        [
            'transaction' => $transaction,
            'assetAccount' => $assetAccount,
        ] = $makeContext();

        expect(fn (): LedgerEntry => LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => 0,
            ])
            ->create())->toThrow(LedgerIntegrityException::class, 'non-zero');
    });

    it('enforces account currency consistency', function () use ($makeContext): void {
        [
            'transaction' => $transaction,
            'assetAccount' => $assetAccount,
        ] = $makeContext();

        expect(fn (): LedgerEntry => LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => 10,
                'currency_code' => 'EUR',
            ])
            ->create())->toThrow(LedgerIntegrityException::class, 'currency');
    });

    it('enforces account ownership per transaction user', function () use ($makeContext): void {
        [
            'currency' => $currency,
            'transaction' => $transaction,
        ] = $makeContext();

        $otherUser = User::factory()->create();

        $foreignAccount = LedgerAccount::factory()
            ->for($otherUser)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Foreign',
            ])
            ->create();

        expect(fn (): LedgerEntry => LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($foreignAccount, 'account')
            ->state([
                'amount' => 25,
            ])
            ->create())->toThrow(LedgerIntegrityException::class, 'same user');
    });
});
