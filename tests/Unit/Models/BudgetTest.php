<?php

declare(strict_types=1);

use App\Enums\CachedAggregateKey;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\CachedAggregate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

describe(Budget::class, function (): void {
    it('exposes cached aggregates morph relationships', function (): void {
        $budget = Budget::factory()->create();

        $aggregate = CachedAggregate::factory()
            ->forAggregatable($budget)
            ->withKey(CachedAggregateKey::CurrentBalance)
            ->state(['scope' => null])
            ->create();

        $freshBudget = $budget->fresh();

        expect($freshBudget->aggregates)->toHaveCount(1);
        expect($freshBudget->aggregates->first()->is($aggregate))->toBeTrue();
        expect($freshBudget->currentBalanceAggregate?->is($aggregate))->toBeTrue();
    });

    it('resolves currentPeriod based on now (not latest)', function (): void {
        Date::setTestNow(CarbonImmutable::parse('2026-01-15 12:00:00'));

        $budget = Budget::factory()->create();

        $january = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt('2026-01-01', '2026-02-01')
            ->create();

        BudgetPeriod::factory()
            ->for($budget)
            ->startingAt('2026-02-01', '2026-03-01')
            ->create();

        $fresh = $budget->fresh('currentPeriod');

        expect($fresh->currentPeriod)->not->toBeNull();
        expect($fresh->currentPeriod?->is($january))->toBeTrue();

        Date::setTestNow();
    });
});
