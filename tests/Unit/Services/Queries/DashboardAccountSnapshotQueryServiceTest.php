<?php

declare(strict_types=1);

use App\Enums\LedgerAccountSubType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\DashboardAccountSnapshotQueryService;
use Carbon\CarbonImmutable;

describe(DashboardAccountSnapshotQueryService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new DashboardAccountSnapshotQueryService();
        $this->user = User::factory()->create();

        $this->currency = Currency::query()->updateOrCreate(
            ['code' => 'USD'],
            ['precision' => 2],
        );
    });

    it('includes transactions on the same day even when effective_at has a time component', function (): void {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-04 12:00:00'));

        $liabilityA = LedgerAccount::factory()
            ->for($this->user)
            ->withSubtype(LedgerAccountSubType::LOAN_PAYABLE)
            ->state(['currency_code' => $this->currency->code])
            ->create();

        $liabilityB = LedgerAccount::factory()
            ->for($this->user)
            ->withSubtype(LedgerAccountSubType::LOAN_PAYABLE)
            ->state(['currency_code' => $this->currency->code])
            ->create();

        $olderTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-12-31 10:00:00'),
                'posted_at' => '2025-12-31',
            ])
            ->create();

        LedgerEntry::factory()
            ->for($olderTransaction, 'transaction')
            ->for($liabilityA, 'account')
            ->state([
                'amount' => '-500.00',
                'amount_base' => '-500.000000',
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $sameDayTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2026-01-04 11:40:46'),
                'posted_at' => '2026-01-04',
            ])
            ->create();

        LedgerEntry::factory()
            ->for($sameDayTransaction, 'transaction')
            ->for($liabilityB, 'account')
            ->state([
                'amount' => '-100.00',
                'amount_base' => '-100.000000',
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $snapshot = $this->service->snapshot($this->user, CarbonImmutable::today());

        expect($snapshot->liabilitiesOwed)->toBe('600.000000');
    });
});

