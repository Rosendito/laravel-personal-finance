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
    beforeEach(function (): void {
        // Currency 'USD' created by global setup
        $this->currency = Currency::query()->where('code', 'USD')->firstOrFail();

        $this->user = User::factory()->create();

        $this->transaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'description' => 'Integrity Test',
                'effective_at' => Date::now(),
                'posted_at' => Date::now()->toDateString(),
            ])
            ->create();

        $this->assetAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $this->currency->code,
                'name' => 'Checking',
            ])
            ->create();
    });

    it('rejects zero amount entries', function (): void {
        expect(fn (): LedgerEntry => LedgerEntry::factory()
            ->for($this->transaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => 0,
            ])
            ->create())->toThrow(LedgerIntegrityException::class, 'non-zero');
    });

    it('enforces account currency consistency', function (): void {
        expect(fn (): LedgerEntry => LedgerEntry::factory()
            ->for($this->transaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => 10,
                'currency_code' => 'EUR',
            ])
            ->create())->toThrow(LedgerIntegrityException::class, 'currency');
    });

    it('enforces account ownership per transaction user', function (): void {
        $otherUser = User::factory()->create();

        $foreignAccount = LedgerAccount::factory()
            ->for($otherUser)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $this->currency->code,
                'name' => 'Foreign',
            ])
            ->create();

        expect(fn (): LedgerEntry => LedgerEntry::factory()
            ->for($this->transaction, 'transaction')
            ->for($foreignAccount, 'account')
            ->state([
                'amount' => 25,
            ])
            ->create())->toThrow(LedgerIntegrityException::class, 'same user');
    });
});
