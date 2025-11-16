<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\AccountBalanceData;
use App\Data\BudgetPeriodStatusData;
use App\Models\User;
use App\Services\Queries\AccountBalanceQueryService;
use App\Services\Queries\BudgetStatusQueryService;
use App\Services\Queries\IncomeStatementQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @deprecated
 */
final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AccountBalanceQueryService $accountBalances,
        BudgetStatusQueryService $budgetStatuses,
        IncomeStatementQueryService $incomeSummary
    ): Response {
        $user = $request->user() ?? User::query()->firstOrFail();

        $now = CarbonImmutable::now();
        $accountData = $accountBalances->totalsForUser($user, $now);
        $budgetData = $budgetStatuses->periodStatus($user, $now->format('Y-m'));
        $incomeData = $incomeSummary->summarize(
            $user,
            $now->startOfMonth(),
            $now->endOfMonth()
        );

        return Inertia::render('Dashboard/Overview', [
            'accountBalances' => $accountData->map(
                static fn (AccountBalanceData $data): array => $data->toArray()
            ),
            'budgetStatuses' => $budgetData->map(
                static fn (BudgetPeriodStatusData $data): array => $data->toArray()
            ),
            'incomeSummary' => $incomeData->toArray(),
        ]);
    }
}
