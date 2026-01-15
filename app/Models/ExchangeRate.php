<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    public function exchangeCurrencyPair(): BelongsTo
    {
        return $this->belongsTo(ExchangeCurrencyPair::class);
    }

    public function exchangeSource(): BelongsTo
    {
        return $this->belongsTo(ExchangeSource::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_currency_pair_id' => 'integer',
            'exchange_source_id' => 'integer',
            'rate' => 'decimal:18',
            'effective_at' => 'datetime',
            'retrieved_at' => 'datetime',
            'is_estimated' => 'boolean',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
