<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExchangeCurrencyPairFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExchangeCurrencyPair extends Model
{
    /** @use HasFactory<ExchangeCurrencyPairFactory> */
    use HasFactory;

    protected $table = 'exchange_currency_pairs';

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency_code', 'code');
    }

    public function quoteCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'quote_currency_code', 'code');
    }

    /**
     * @return HasMany<ExchangeRate>
     */
    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'exchange_currency_pair_id');
    }

    /**
     * @return BelongsToMany<ExchangeSource>
     */
    public function exchangeSources(): BelongsToMany
    {
        return $this->belongsToMany(ExchangeSource::class, 'exchange_currency_pair_exchange_source')
            ->withTimestamps();
    }

    public function isSupportedBy(ExchangeSource $source): bool
    {
        return $this->exchangeSources()
            ->whereKey($source->id)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_currency_code' => 'string',
            'quote_currency_code' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
