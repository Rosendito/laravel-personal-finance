import { Head } from "@inertiajs/react";

import { AccountListCard } from "@/components/dashboard/AccountListCard";
import { BudgetProgressCard } from "@/components/dashboard/BudgetProgressCard";
import { SummaryStats } from "@/components/dashboard/SummaryStats";
import { AppShell } from "@/components/layout/AppShell";
import { PageHeader } from "@/components/layout/PageHeader";
import { Button } from "@/components/ui/button";
import type { PageProps } from "@/types/page";

type DashboardOverviewProps = PageProps<{
    accountBalances: App.Data.AccountBalanceData[];
    budgetStatuses: App.Data.BudgetAllocationStatusData[];
    incomeSummary: App.Data.IncomeStatementSummaryData;
}>;

const Overview = ({
    accountBalances,
    budgetStatuses,
    incomeSummary,
}: DashboardOverviewProps) => {
    const primaryCurrency = accountBalances[0]?.currency_code ?? "USD";

    return (
        <AppShell>
            <Head title="Overview" />
            <PageHeader
                eyebrow="Dashboard"
                title="Overview"
                description="Visualiza el estado general de tus cuentas, presupuestos y flujo mensual."
                actions={
                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" className="text-sm">
                            Planear presupuesto
                        </Button>
                        <Button className="text-sm">
                            Registrar movimiento
                        </Button>
                    </div>
                }
            />

            <div className="mt-8 space-y-6">
                <SummaryStats
                    summary={incomeSummary}
                    currency={primaryCurrency}
                />
                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <BudgetProgressCard allocations={budgetStatuses} />
                    </div>
                    <div>
                        <AccountListCard accounts={accountBalances} />
                    </div>
                </div>
            </div>
        </AppShell>
    );
};

export default Overview;
