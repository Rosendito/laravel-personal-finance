<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntryMethod;
use Database\Factories\MerchantProductPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MerchantProductPrice extends Model
{
    /** @use HasFactory<MerchantProductPriceFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'merchant_product_prices';

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MerchantProductListing::class, 'merchant_product_listing_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'merchant_product_listing_id' => 'integer',
            'observed_at' => 'datetime',
            'entry_method' => EntryMethod::class,
            'currency' => 'string',
            'price_regular' => 'decimal:6',
            'price_current' => 'decimal:6',
            'is_promo' => 'boolean',
            'promo_type' => 'string',
            'promo_description' => 'string',
            'tax_included' => 'boolean',
            'stock_status' => 'string',
            'raw_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
