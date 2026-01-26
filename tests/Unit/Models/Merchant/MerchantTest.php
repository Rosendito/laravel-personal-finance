<?php

declare(strict_types=1);

use App\Enums\MerchantType;
use App\Models\Merchant\Merchant;
use App\Models\Merchant\MerchantLocation;
use App\Models\Merchant\MerchantProductListing;
use Carbon\CarbonInterface;

describe(Merchant::class, function (): void {
    it('has locations and product listings', function (): void {
        $merchant = Merchant::factory()->create();

        $location = MerchantLocation::factory()->for($merchant)->create();
        $listing = MerchantProductListing::factory()->for($merchant)->create();

        $freshMerchant = $merchant->fresh();

        expect($freshMerchant->locations)->toHaveCount(1);
        expect($freshMerchant->locations->first()->is($location))->toBeTrue();
        expect($freshMerchant->productListings)->toHaveCount(1);
        expect($freshMerchant->productListings->first()->is($listing))->toBeTrue();
    });

    it('casts attributes to expected types', function (): void {
        $merchant = Merchant::factory()->create([
            'merchant_type' => MerchantType::STORE,
            'base_url' => 'https://example.test',
            'notes' => 'Preferred vendor',
        ]);

        expect($merchant->merchant_type)->toBeInstanceOf(MerchantType::class);
        expect($merchant->base_url)->toBe('https://example.test');
        expect($merchant->notes)->toBe('Preferred vendor');
        expect($merchant->created_at)->toBeInstanceOf(CarbonInterface::class);
        expect($merchant->updated_at)->toBeInstanceOf(CarbonInterface::class);
    });
});
