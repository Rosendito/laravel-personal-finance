<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\UpdateBudgetPeriodAggregatesAction;
use App\Models\BudgetPeriod;
use Illuminate\Console\Command;

final class SyncBudgetPeriodSpent extends Command
{
    protected $signature = 'budget:sync-spent {--period= : The ID of a specific budget period to sync}';

    protected $description = 'Sync spent amounts for budget periods';

    public function __construct(
        private readonly UpdateBudgetPeriodAggregatesAction $updateAggregatesAction,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodId = $this->option('period');

        if ($periodId !== null) {
            return $this->syncSinglePeriod((int) $periodId);
        }

        return $this->syncAllPeriods();
    }

    private function syncSinglePeriod(int $periodId): int
    {
        $period = BudgetPeriod::query()->find($periodId);

        if ($period === null) {
            $this->error("Budget period with ID {$periodId} not found.");

            return self::FAILURE;
        }

        $this->info("Syncing budget period #{$periodId}...");

        $this->updatePeriodSpent($period);

        $this->info("Budget period #{$periodId} synced successfully.");

        return self::SUCCESS;
    }

    private function syncAllPeriods(): int
    {
        $periods = BudgetPeriod::query()->get();

        if ($periods->isEmpty()) {
            $this->warn('No budget periods found.');

            return self::SUCCESS;
        }

        $this->info("Syncing {$periods->count()} budget period(s)...");

        $bar = $this->output->createProgressBar($periods->count());
        $bar->start();

        foreach ($periods as $period) {
            $this->updatePeriodSpent($period);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully synced {$periods->count()} budget period(s).");

        return self::SUCCESS;
    }

    private function updatePeriodSpent(BudgetPeriod $period): void
    {
        $this->updateAggregatesAction->execute($period);
    }
}
