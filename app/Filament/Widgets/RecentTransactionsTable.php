<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Data\Dashboard\RecentTransactionData;
use App\Helpers\MoneyFormatter;
use App\Models\LedgerTransaction;
use App\Services\Queries\DashboardRecentTransactionsQueryService;
use App\Support\Dates\MonthlyDateRange;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

final class RecentTransactionsTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<int, RecentTransactionData>
     */
    private array $recentById = [];

    public function table(Table $table): Table
    {
        $user = Auth::user();

        if ($user === null) {
            return $table
                ->query(LedgerTransaction::query()->whereRaw('1 = 0'))
                ->columns([]);
        }

        [$start, $end] = $this->dateRange();
        $currency = config('finance.currency.default', 'USD');

        $recent = resolve(DashboardRecentTransactionsQueryService::class)
            ->recent($user, $start, $end, 15);

        $this->recentById = $recent
            ->keyBy(static fn (RecentTransactionData $data): int => (int) $data->transactionId)
            ->all();

        $transactionIds = array_keys($this->recentById);

        if ($transactionIds === []) {
            return $table
                ->query(LedgerTransaction::query()->whereRaw('1 = 0'))
                ->columns([
                    TextColumn::make('empty')
                        ->label('Sin transacciones en el rango'),
                ])
                ->paginated(false);
        }

        return $table
            ->query(
                LedgerTransaction::query()
                    ->with(['category', 'budgetPeriod'])
                    ->whereIn('id', $transactionIds)
                    ->latest('effective_at')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('effective_at')
                    ->label('Fecha')
                    ->date(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->placeholder('Sin categoría'),
                TextColumn::make('budgetPeriod')
                    ->label('Periodo presupuesto')
                    ->state(function (LedgerTransaction $transaction): ?string {
                        $data = $this->recentById[$transaction->id] ?? null;

                        return $data?->budgetPeriodLabel;
                    })
                    ->placeholder('—'),
                TextColumn::make('expense')
                    ->label('Gasto')
                    ->state(function (LedgerTransaction $transaction) use ($currency): string {
                        $data = $this->recentById[$transaction->id] ?? null;

                        return MoneyFormatter::format($data?->expenseAmount ?? '0', $currency);
                    }),
                TextColumn::make('income')
                    ->label('Ingreso')
                    ->state(function (LedgerTransaction $transaction) use ($currency): string {
                        $data = $this->recentById[$transaction->id] ?? null;

                        return MoneyFormatter::format($data?->incomeAmount ?? '0', $currency);
                    }),
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
            : null;

        $endDate = is_string($end) && $end !== ''
            ? CarbonImmutable::parse($end)
            : null;

        if (! $startDate instanceof CarbonImmutable && ! $endDate instanceof CarbonImmutable) {
            return MonthlyDateRange::forDate(CarbonImmutable::today());
        }

        $startDate ??= $endDate->startOfMonth();
        $endDate ??= $startDate->endOfMonth();

        if ($endDate->lessThan($startDate)) {
            $endDate = $startDate->endOfMonth();
        }

        return [$startDate, $endDate];
    }
}
