<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\CachedAggregateKey;
use App\Enums\CachedAggregateScope;
use App\Models\CachedAggregate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCachedAggregates
{
    public function aggregates(): MorphMany
    {
        return $this->morphMany(CachedAggregate::class, 'aggregatable');
    }

    public function cachedAggregate(
        CachedAggregateKey|string $key,
        CachedAggregateScope|string|null $scope = null,
    ): ?CachedAggregate {
        return $this->aggregateQuery($key, $scope)->first();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function upsertCachedAggregate(
        CachedAggregateKey|string $key,
        array $values,
        CachedAggregateScope|string|null $scope = null,
    ): CachedAggregate {
        return $this->aggregates()->updateOrCreate(
            [
                'key' => $this->resolveAggregateKey($key),
                'scope' => $this->resolveAggregateScope($scope),
            ],
            $values,
        );
    }

    public function forgetCachedAggregate(
        CachedAggregateKey|string $key,
        CachedAggregateScope|string|null $scope = null,
    ): void {
        $this->aggregateQuery($key, $scope)->delete();
    }

    private function aggregateQuery(
        CachedAggregateKey|string $key,
        CachedAggregateScope|string|null $scope = null,
    ): Builder {
        $query = $this->aggregates()->where('key', $this->resolveAggregateKey($key));

        $scopeValue = $this->resolveAggregateScope($scope);

        if ($scopeValue === null) {
            return $query->whereNull('scope');
        }

        return $query->where('scope', $scopeValue);
    }

    private function resolveAggregateKey(CachedAggregateKey|string $key): string
    {
        return $key instanceof CachedAggregateKey ? $key->value : $key;
    }

    private function resolveAggregateScope(CachedAggregateScope|string|null $scope): ?string
    {
        return $scope instanceof CachedAggregateScope ? $scope->value : $scope;
    }
}
