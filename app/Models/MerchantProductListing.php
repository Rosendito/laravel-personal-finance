<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MerchantProductListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MerchantProductListing extends Model
{
    /** @use HasFactory<MerchantProductListingFactory> */
    use HasFactory;

    protected $table = 'merchant_product_listings';

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function merchantLocation(): BelongsTo
    {
        return $this->belongsTo(MerchantLocation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<MerchantProductPrice>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(MerchantProductPrice::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'merchant_id' => 'integer',
            'merchant_location_id' => 'integer',
            'product_id' => 'integer',
            'external_id' => 'string',
            'external_url' => 'string',
            'title' => 'string',
            'brand_raw' => 'string',
            'size_value' => 'decimal:6',
            'size_unit' => 'string',
            'pack_quantity' => 'integer',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
