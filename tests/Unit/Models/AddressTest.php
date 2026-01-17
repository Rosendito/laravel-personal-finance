<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\User;

describe(Address::class, function (): void {
    it('persists a polymorphic relation with defaults', function (): void {
        $user = User::factory()->create();

        $address = Address::factory()
            ->for($user, 'addressable')
            ->state([
                'country_code' => 'US',
                'address_line1' => '123 Main St',
                'is_default' => true,
            ])
            ->create();

        $freshAddress = $address->fresh();

        expect($freshAddress->addressable)->toBeInstanceOf(User::class);
        expect($freshAddress->addressable->is($user))->toBeTrue();
        expect($freshAddress->country_code)->toBe('US');
        expect($freshAddress->address_line1)->toBe('123 Main St');
        expect($freshAddress->is_default)->toBeTrue();
    });
});
