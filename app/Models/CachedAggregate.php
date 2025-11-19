<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CachedAggregateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class CachedAggregate extends Model
{
    /** @use HasFactory<CachedAggregateFactory> */
    use HasFactory;

    public function aggregatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aggregatable_id' => 'integer',
            'aggregatable_type' => 'string',
            'key' => 'string',
            'scope' => 'string',
            'value_decimal' => 'decimal:6',
            'value_int' => 'integer',
            'value_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
