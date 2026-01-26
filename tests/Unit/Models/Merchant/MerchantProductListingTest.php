<?php

declare(strict_types=1);

use App\Models\Merchant\Merchant;
use App\Models\Merchant\MerchantLocation;
use App\Models\Merchant\MerchantProductListing;
use App\Models\Merchant\MerchantProductPrice;
use App\Models\Product;
use Carbon\CarbonInterface;

describe(MerchantProductListing::class, function (): void {
    it('belongs to a merchant, location, product, and has prices', function (): void {
        $merchant = Merchant::factory()->create();
        $location = MerchantLocation::factory()->for($merchant)->create();
        $product = Product::factory()->create();

        $listing = MerchantProductListing::factory()
            ->for($merchant)
            ->for($location, 'merchantLocation')
            ->for($product, 'product')
            ->create();

        $price = MerchantProductPrice::factory()
            ->for($listing, 'listing')
            ->create();

        $freshListing = $listing->fresh();

        expect($freshListing->merchant->is($merchant))->toBeTrue();
        expect($freshListing->merchantLocation->is($location))->toBeTrue();
        expect($freshListing->product->is($product))->toBeTrue();
        expect($freshListing->prices)->toHaveCount(1);
        expect($freshListing->prices->first()->is($price))->toBeTrue();
    });

    it('casts attributes to expected types', function (): void {
        $listing = MerchantProductListing::factory()->create([
            'size_value' => '1.250000',
            'size_unit' => 'kg',
            'pack_quantity' => 2,
            'last_seen_at' => now(),
            'metadata' => ['source' => 'test'],
        ]);

        expect($listing->merchant_id)->toBeInt();
        expect($listing->title)->toBeString();
        expect($listing->size_value)->toBeString();
        expect($listing->pack_quantity)->toBeInt();
        expect($listing->is_active)->toBeTrue();
        expect($listing->last_seen_at)->toBeInstanceOf(CarbonInterface::class);
        expect($listing->metadata)->toBeArray()->toHaveKey('source', 'test');
    });
});
