<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\Merchant\Merchant;
use App\Models\Merchant\MerchantLocation;
use Carbon\CarbonInterface;

describe(MerchantLocation::class, function (): void {
    it('belongs to a merchant and has one address', function (): void {
        $merchant = Merchant::factory()->create();
        $location = MerchantLocation::factory()->for($merchant)->create();
        $address = Address::factory()->for($location, 'addressable')->create();

        $freshLocation = $location->fresh();

        expect($freshLocation->merchant->is($merchant))->toBeTrue();
        expect($freshLocation->address)->toBeInstanceOf(Address::class);
        expect($freshLocation->address->is($address))->toBeTrue();
    });

    it('casts attributes to expected types', function (): void {
        $location = MerchantLocation::factory()->create();

        expect($location->merchant_id)->toBeInt();
        expect($location->name)->toBeString();
        expect($location->created_at)->toBeInstanceOf(CarbonInterface::class);
        expect($location->updated_at)->toBeInstanceOf(CarbonInterface::class);
    });
});
