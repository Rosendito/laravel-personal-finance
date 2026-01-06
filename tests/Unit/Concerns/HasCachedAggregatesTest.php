<?php

declare(strict_types=1);

use App\Concerns\HasCachedAggregates;
use App\Enums\CachedAggregateKey;
use App\Enums\CachedAggregateScope;
use App\Models\Budget;
use App\Models\CachedAggregate;

describe(HasCachedAggregates::class, function (): void {
    it('upserts and fetches aggregates by key and nullable scope', function (): void {
        $budget = Budget::factory()->create();

        $created = $budget->upsertCachedAggregate(
            key: CachedAggregateKey::CurrentBalance,
            values: ['value_int' => 100],
            scope: null,
        );

        expect($created)->toBeInstanceOf(CachedAggregate::class);
        expect($created->key)->toBe(CachedAggregateKey::CurrentBalance->value);
        expect($created->scope)->toBeNull();
        expect($created->value_int)->toBe(100);

        $fetched = $budget->cachedAggregate(
            key: CachedAggregateKey::CurrentBalance->value,
            scope: null,
        );

        expect($fetched)->not->toBeNull();
        expect($fetched?->is($created))->toBeTrue();
    });

    it('updates an existing aggregate when upserting with a non-null scope', function (): void {
        $budget = Budget::factory()->create();

        $first = $budget->upsertCachedAggregate(
            key: CachedAggregateKey::Spent,
            values: ['value_int' => 10],
            scope: CachedAggregateScope::Monthly,
        );

        $second = $budget->upsertCachedAggregate(
            key: CachedAggregateKey::Spent->value,
            values: ['value_int' => 20],
            scope: CachedAggregateScope::Monthly->value,
        );

        expect($second->is($first))->toBeTrue();
        expect($second->value_int)->toBe(20);

        $count = $budget->aggregates()
            ->where('key', CachedAggregateKey::Spent->value)
            ->where('scope', CachedAggregateScope::Monthly->value)
            ->count();

        expect($count)->toBe(1);
    });

    it('forgets a cached aggregate for a given key and scope', function (): void {
        $budget = Budget::factory()->create();

        $aggregate = CachedAggregate::factory()
            ->forAggregatable($budget)
            ->withKey(CachedAggregateKey::Spent)
            ->state(['scope' => CachedAggregateScope::Daily->value])
            ->create();

        $budget->forgetCachedAggregate(
            key: CachedAggregateKey::Spent,
            scope: CachedAggregateScope::Daily,
        );

        $exists = CachedAggregate::query()->whereKey($aggregate->getKey())->exists();
        expect($exists)->toBeFalse();
    });
});
