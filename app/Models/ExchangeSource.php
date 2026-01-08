<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExchangeSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExchangeSource extends Model
{
    /** @use HasFactory<ExchangeSourceFactory> */
    use HasFactory;

    protected $table = 'exchange_sources';

    /**
     * @return HasMany<ExchangeRate>
     */
    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => 'string',
            'name' => 'string',
            'type' => 'string',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
