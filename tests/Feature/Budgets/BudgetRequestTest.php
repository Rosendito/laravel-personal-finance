<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\User;

describe('Budget requests', function (): void {
    it('rejects duplicate budget names within the same period on store', function (): void {
        $user = User::factory()->create();
        Budget::factory()
            ->for($user)
            ->forPeriod('2025-11')
            ->state(['name' => 'Household'])
            ->create();

        $this->actingAs($user);

        $response = $this->postJson(route('budgets.store'), [
            'name' => 'Household',
            'period' => '2025-11',
            'is_active' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertInvalid(['name']);
    });

    it('rejects duplicate budget names within the same period on update', function (): void {
        $user = User::factory()->create();
        $budget = Budget::factory()
            ->for($user)
            ->forPeriod('2025-11')
            ->state(['name' => 'Groceries'])
            ->create();
        Budget::factory()
            ->for($user)
            ->forPeriod('2025-11')
            ->state(['name' => 'Housing'])
            ->create();

        $this->actingAs($user);

        $response = $this->putJson(route('budgets.update', $budget), [
            'name' => 'Housing',
            'period' => '2025-11',
            'is_active' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertInvalid(['name']);
    });
});
