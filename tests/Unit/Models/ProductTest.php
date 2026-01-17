<?php

declare(strict_types=1);

use App\Models\Merchant\Merchant;
use App\Models\Merchant\MerchantProductListing;
use App\Models\Product;
use Carbon\CarbonInterface;

describe(Product::class, function (): void {
    it('has merchant listings', function (): void {
        $product = Product::factory()->create();
        $merchant = Merchant::factory()->create();

        $listing = MerchantProductListing::factory()
            ->for($merchant)
            ->for($product, 'product')
            ->create();

        $freshProduct = $product->fresh();

        expect($freshProduct->merchantListings)->toHaveCount(1);
        expect($freshProduct->merchantListings->first()->is($listing))->toBeTrue();
    });

    it('casts attributes to expected types', function (): void {
        $product = Product::factory()->create([
            'canonical_size_value' => '2.500000',
            'canonical_size_unit' => 'kg',
            'barcode' => '1234567890123',
            'notes' => 'Staple pantry item',
        ]);

        expect($product->name)->toBeString();
        expect($product->canonical_size_value)->toBeString();
        expect($product->canonical_size_unit)->toBe('kg');
        expect($product->barcode)->toBe('1234567890123');
        expect($product->notes)->toBe('Staple pantry item');
        expect($product->created_at)->toBeInstanceOf(CarbonInterface::class);
        expect($product->updated_at)->toBeInstanceOf(CarbonInterface::class);
    });
});
