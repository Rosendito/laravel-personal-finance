<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Data\Dashboard\CategoryTotalData;
use App\Helpers\MoneyFormatter;
use App\Models\User;
use App\Services\Queries\DashboardSpendingByCategoryQueryService;
use Carbon\CarbonImmutable;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class SpendingByCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Gastos por categoría';

    protected int|string|array $columnSpan = 1;

    private ?Collection $totalsCache = null;

    public function getDescription(): ?string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $currency = config('finance.currency.default', 'USD');
        $totalSpent = $this->totalSpent($user);

        return sprintf('Total gastado: %s', MoneyFormatter::format($totalSpent, $currency));
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $totals = $this->totals($user);

        if ($totals->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Gastos',
                        'data' => [0],
                        'backgroundColor' => ['#e5e7eb'],
                    ],
                ],
                'labels' => ['Sin datos'],
            ];
        }
        $labels = $totals->map(static fn ($row) => $row->name)->all();
        $data = $totals->map(static fn ($row) => (float) $row->total)->all();
        $colors = $this->resolveColors($totals->all());

        return [
            'datasets' => [
                [
                    'label' => 'Gastos',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(): array
    {
        $start = $this->pageFilters['start_at'] ?? null;
        $end = $this->pageFilters['end_at'] ?? null;

        $startDate = is_string($start) && $start !== ''
            ? CarbonImmutable::parse($start)
            : CarbonImmutable::today()->subMonth()->setDay(15);

        $endDate = is_string($end) && $end !== ''
            ? CarbonImmutable::parse($end)
            : CarbonImmutable::today()->setDay(15);

        if ($endDate->lessThanOrEqualTo($startDate)) {
            $endDate = $startDate->addMonth();
        }

        return [$startDate, $endDate];
    }

    /**
     * @return Collection<int, CategoryTotalData>
     */
    private function totals(User $user): Collection
    {
        if ($this->totalsCache !== null) {
            /** @var Collection<int, CategoryTotalData> $totals */
            $totals = $this->totalsCache;

            return $totals;
        }

        [$start, $end] = $this->dateRange();

        /** @var Collection<int, CategoryTotalData> $totals */
        $totals = app(DashboardSpendingByCategoryQueryService::class)->totals($user, $start, $end);

        $this->totalsCache = $totals;

        return $totals;
    }

    private function totalSpent(User $user): float
    {
        return $this->totals($user)
            ->sum(static fn (CategoryTotalData $row): float => (float) $row->total);
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, string>
     */
    private function resolveColors(array $rows): array
    {
        $palette = $this->palette();
        $paletteSize = count($palette);

        $colors = [];

        foreach ($rows as $row) {
            if (($row->categoryId ?? null) === null) {
                $colors[] = Color::convertToRgb(Color::Zinc[400]);

                continue;
            }

            $index = $this->resolveColorIndexFromId((int) $row->categoryId, $paletteSize);
            $colors[] = Color::convertToRgb($palette[$index]);
        }

        return $colors;
    }

    /**
     * @return array<int, string>
     */
    private function palette(): array
    {
        return [
            Color::Red[500],
            Color::Orange[500],
            Color::Amber[500],
            Color::Yellow[500],
            Color::Lime[500],
            Color::Green[500],
            Color::Emerald[500],
            Color::Teal[500],
            Color::Cyan[500],
            Color::Sky[500],
            Color::Blue[500],
            Color::Indigo[500],
            Color::Violet[500],
            Color::Purple[500],
            Color::Fuchsia[500],
            Color::Pink[500],
            Color::Rose[500],
            Color::Slate[500],
            Color::Gray[500],
            Color::Stone[500],
        ];
    }

    private function resolveColorIndexFromId(int $id, int $paletteSize = 30): int
    {
        return abs($id) % $paletteSize;
    }
}
