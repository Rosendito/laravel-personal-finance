<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantType;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Merchant extends Model
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    protected $table = 'merchant_merchants';

    /**
     * @return HasMany<MerchantLocation>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(MerchantLocation::class);
    }

    /**
     * @return HasMany<MerchantProductListing>
     */
    public function productListings(): HasMany
    {
        return $this->hasMany(MerchantProductListing::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'string',
            'merchant_type' => MerchantType::class,
            'base_url' => 'string',
            'notes' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
