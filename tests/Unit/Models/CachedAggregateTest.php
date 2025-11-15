<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\CachedAggregate;

describe(CachedAggregate::class, function (): void {
    it('morphs back to its aggregatable model', function (): void {
        $budget = Budget::factory()->create();

        $aggregate = CachedAggregate::factory()
            ->forAggregatable($budget)
            ->create();

        $related = $aggregate->aggregatable;

        expect($related)->toBeInstanceOf(Budget::class);
        expect($related->is($budget))->toBeTrue();
    });
});
