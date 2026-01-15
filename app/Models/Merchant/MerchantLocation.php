<?php

declare(strict_types=1);

namespace App\Models\Merchant;

use App\Models\Address;
use Database\Factories\Merchant\MerchantLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

final class MerchantLocation extends Model
{
    /** @use HasFactory<MerchantLocationFactory> */
    use HasFactory;

    protected $table = 'merchant_locations';

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
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
            'merchant_id' => 'integer',
            'name' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
