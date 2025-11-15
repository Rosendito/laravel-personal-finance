<?php

declare(strict_types=1);

use App\Enums\CachedAggregateKey;
use App\Models\Budget;
use App\Models\CachedAggregate;

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
});
