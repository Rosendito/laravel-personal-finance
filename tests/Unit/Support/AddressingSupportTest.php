<?php

declare(strict_types=1);

use App\Support\Addressing\AddressingSupport;

describe(AddressingSupport::class, function (): void {
    it('maps required package fields to model columns', function (): void {
        $required = AddressingSupport::requiredColumns('US');

        expect($required)->toContain('address_line1');
        expect($required)->toContain('locality');
        expect($required)->toContain('given_name');
        expect($required)->toContain('family_name');
    });

    it('provides country options keyed by ISO2 code', function (): void {
        $options = AddressingSupport::countryOptions('en');

        expect($options)->toBeArray();
        expect($options)->toHaveKey('US');
    });
});
