<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\LedgerTransaction;
use App\Models\User;

describe(LedgerTransaction::class, function (): void {
    it('filters only uncategorized when uncategorized is true', function (): void {
        $user = User::factory()->create();

        $category = Category::factory()->for($user)->create();

        $categorized = LedgerTransaction::factory()
            ->for($user)
            ->withCategory($category)
            ->create();

        $uncategorized = LedgerTransaction::factory()
            ->for($user)
            ->create();

        $results = LedgerTransaction::query()
            ->where('user_id', $user->id)
            ->whereCategoryFilter(true, [])
            ->pluck('id')
            ->all();

        expect($results)->toBe([$uncategorized->id]);
        expect($results)->not->toContain($categorized->id);
    });

    it('filters by selected categories when uncategorized is false', function (): void {
        $user = User::factory()->create();

        $food = Category::factory()->for($user)->create();
        $rent = Category::factory()->for($user)->create();

        $foodTx = LedgerTransaction::factory()->for($user)->withCategory($food)->create();
        $rentTx = LedgerTransaction::factory()->for($user)->withCategory($rent)->create();
        $uncategorized = LedgerTransaction::factory()->for($user)->create();

        $results = LedgerTransaction::query()
            ->where('user_id', $user->id)
            ->whereCategoryFilter(false, [$food->id])
            ->pluck('id')
            ->all();

        expect($results)->toBe([$foodTx->id]);
        expect($results)->not->toContain($rentTx->id);
        expect($results)->not->toContain($uncategorized->id);
    });
});
