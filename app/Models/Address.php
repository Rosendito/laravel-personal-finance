<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'addressable_id' => 'integer',
            'addressable_type' => 'string',
            'country_code' => 'string',
            'administrative_area' => 'string',
            'locality' => 'string',
            'dependent_locality' => 'string',
            'postal_code' => 'string',
            'sorting_code' => 'string',
            'address_line1' => 'string',
            'address_line2' => 'string',
            'address_line3' => 'string',
            'organization' => 'string',
            'given_name' => 'string',
            'additional_name' => 'string',
            'family_name' => 'string',
            'label' => 'string',
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
