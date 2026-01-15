<?php

declare(strict_types=1);

use App\Enums\EntryMethod;
use App\Models\Merchant\MerchantProductListing;
use App\Models\Merchant\MerchantProductPrice;
use Carbon\CarbonInterface;

describe(MerchantProductPrice::class, function (): void {
    it('belongs to a listing', function (): void {
        $price = MerchantProductPrice::factory()->create();

        expect($price->listing)->toBeInstanceOf(MerchantProductListing::class);
    });

    it('casts attributes to expected types', function (): void {
        $price = MerchantProductPrice::factory()->create([
            'entry_method' => EntryMethod::MANUAL,
            'price_regular' => '9.990000',
            'price_current' => '7.990000',
            'tax_included' => true,
        ]);

        expect($price->entry_method)->toBeInstanceOf(EntryMethod::class);
        expect($price->observed_at)->toBeInstanceOf(CarbonInterface::class);
        expect($price->price_current)->toBeString();
        expect($price->tax_included)->toBeTrue();
        expect($price->created_at)->toBeInstanceOf(CarbonInterface::class);
        expect($price->updated_at)->toBeNull();
    });
});
