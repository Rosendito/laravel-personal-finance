<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Data\Dashboard\BudgetProgressData;
use App\Helpers\MoneyFormatter;
use App\Models\Budget;
use App\Services\Queries\DashboardBudgetProgressQueryService;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

final class BudgetProgressTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<int, BudgetProgressData>
     */
    private array $progressByBudget = [];

    public function table(Table $table): Table
    {
        $user = Auth::user();

        if ($user === null) {
            return $table
                ->query(Budget::query()->whereRaw('1 = 0'))
                ->columns([]);
        }

        [$start, $end] = $this->dateRange();
        $currency = config('finance.currency.default', 'USD');

        $progress = resolve(DashboardBudgetProgressQueryService::class)
            ->progress($user, $start, $end);

        $this->progressByBudget = $progress
            ->keyBy(static fn (BudgetProgressData $data): int => $data->budgetId)
            ->all();

        $budgetIds = array_keys($this->progressByBudget);

        if ($budgetIds === []) {
            return $table
                ->query(Budget::query()->whereRaw('1 = 0'))
                ->columns([
                    TextColumn::make('empty')->label('Sin presupuestos en rango'),
                ])
                ->paginated(false);
        }

        return $table
            ->query(
                Budget::query()
                    ->whereIn('id', $budgetIds)
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Presupuesto'),
                TextColumn::make('period')
                    ->label('Periodo')
                    ->state(fn (Budget $budget): string => $this->progressByBudget[$budget->id]->periodLabel),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->state(fn (Budget $budget): string => MoneyFormatter::format(
                        $this->progressByBudget[$budget->id]->amount,
                        $currency,
                    )),
                TextColumn::make('spent')
                    ->label('Gastado')
                    ->state(fn (Budget $budget): string => MoneyFormatter::format(
                        $this->progressByBudget[$budget->id]->spent,
                        $currency,
                    )),
                TextColumn::make('remaining')
                    ->label('Restante')
                    ->state(fn (Budget $budget): string => MoneyFormatter::format(
                        $this->progressByBudget[$budget->id]->remaining,
                        $currency,
                    )),
                TextColumn::make('usage')
                    ->label('% Uso')
                    ->state(fn (Budget $budget): string => sprintf('%.2f%%', (float) $this->progressByBudget[$budget->id]->usagePercent)),
            ])
            ->paginated(false);
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
}
