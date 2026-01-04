<?php

declare(strict_types=1);

use App\Data\Dashboard\CategoryTotalData;
use App\Filament\Widgets\SpendingByCategoryChart;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;

describe(SpendingByCategoryChart::class, function (): void {
    it('does not create a synthetic "Otros" bucket and shows all categories', function (): void {
        $user = User::factory()->create();
        actingAs($user);

        /** @var Collection<int, CategoryTotalData> $totals */
        $totals = collect([
            new CategoryTotalData(categoryId: 10, name: 'Criptos', total: '160.00'),
            new CategoryTotalData(categoryId: 11, name: 'Mercado', total: '140.00'),
            new CategoryTotalData(categoryId: 12, name: 'Eventos festivos', total: '120.00'),
            new CategoryTotalData(categoryId: 13, name: 'Salidas', total: '110.00'),
            new CategoryTotalData(categoryId: 14, name: 'Ropa', total: '100.00'),
            new CategoryTotalData(categoryId: 15, name: 'Consultas medicas', total: '90.00'),
            new CategoryTotalData(categoryId: 16, name: 'Chuches', total: '80.00'),
            new CategoryTotalData(categoryId: 17, name: 'Carro', total: '70.00'),
            new CategoryTotalData(categoryId: 18, name: 'Otros', total: '60.00'), // Real category, not a synthetic bucket.
            new CategoryTotalData(categoryId: null, name: 'Sin categoría', total: '50.00'),
        ]);

        $widget = app(SpendingByCategoryChart::class);

        $totalsCacheProperty = new ReflectionProperty($widget, 'totalsCache');
        $totalsCacheProperty->setAccessible(true);
        $totalsCacheProperty->setValue($widget, $totals);

        $getDataMethod = new ReflectionMethod($widget, 'getData');
        $getDataMethod->setAccessible(true);

        /** @var array{datasets: array<int, array{data: array<int, float>}>, labels: array<int, string>} $data */
        $data = $getDataMethod->invoke($widget);

        expect($data['labels'])->toHaveCount(10);
        expect($data['labels'])->toBe($totals->map(static fn (CategoryTotalData $row): string => $row->name)->all());

        $otrosCount = collect($data['labels'])->filter(static fn (string $label): bool => $label === 'Otros')->count();
        expect($otrosCount)->toBe(1);

        Auth::logout();
    });
});
