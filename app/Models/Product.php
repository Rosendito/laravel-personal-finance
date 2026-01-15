<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Merchant\MerchantProductListing;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Fixed list of allowed units, ordered by DB usage (most used first).
     *
     * @return array<string, string>
     */
    public static function canonicalSizeUnitOptions(): array
    {
        $units = [
            'kg',
            'g',
            'l',
            'ml',
            'oz',
        ];

        /** @var array<string, int> $countsByUnit */
        $countsByUnit = self::query()
            ->whereNotNull('canonical_size_unit')
            ->whereIn('canonical_size_unit', $units)
            ->selectRaw('canonical_size_unit, COUNT(*) as aggregate')
            ->groupBy('canonical_size_unit')
            ->pluck('aggregate', 'canonical_size_unit')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();

        $orderIndex = array_flip($units);

        usort($units, static function (string $a, string $b) use ($countsByUnit, $orderIndex): int {
            $countA = $countsByUnit[$a] ?? 0;
            $countB = $countsByUnit[$b] ?? 0;

            if ($countA === $countB) {
                return ($orderIndex[$a] ?? 0) <=> ($orderIndex[$b] ?? 0);
            }

            return $countB <=> $countA;
        });

        return array_combine($units, $units) ?: [];
    }

    /**
     * @return HasMany<MerchantProductListing>
     */
    public function merchantListings(): HasMany
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
            'brand' => 'string',
            'category' => 'string',
            'canonical_size_value' => 'decimal:6',
            'canonical_size_unit' => 'string',
            'barcode' => 'string',
            'notes' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
