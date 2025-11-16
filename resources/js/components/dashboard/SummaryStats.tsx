import { ArrowDownRight, ArrowUpRight } from "lucide-react";

import { Card, CardContent } from "@/components/ui/card";
import { formatCurrency } from "@/lib/format";

type SummaryStatsProps = {
    summary: App.Data.IncomeStatementSummaryData;
    currency?: string;
};

const toNumber = (value: string | number): number => {
    const numericValue = typeof value === "string" ? Number(value) : value;

    return Number.isFinite(numericValue) ? numericValue : 0;
};

export const SummaryStats = ({
    summary,
    currency = "USD",
}: SummaryStatsProps) => {
    const totalIncome = toNumber(summary.total_income);
    const totalExpense = toNumber(summary.total_expense);
    const netIncome = toNumber(summary.net_income);

    const coverage = totalExpense > 0 ? (totalIncome / totalExpense) * 100 : 0;
    const expenseShare =
        totalIncome > 0 ? (totalExpense / totalIncome) * 100 : 0;
    const savingsRate =
        totalIncome > 0
            ? ((totalIncome - totalExpense) / totalIncome) * 100
            : 0;

    const stats = [
        {
            id: "income",
            label: "Ingresos del mes",
            amount: formatCurrency(totalIncome, currency),
            helper: `Cubre ${coverage.toFixed(0)}% de tus gastos`,
            trend: coverage >= 100 ? "Superávit" : "Estable",
            trendIsPositive: true,
        },
        {
            id: "expense",
            label: "Gastos del mes",
            amount: formatCurrency(totalExpense, currency),
            helper: `${expenseShare.toFixed(0)}% de tus ingresos`,
            trend: expenseShare <= 60 ? "Ligero" : "Alerta",
            trendIsPositive: expenseShare <= 60,
        },
        {
            id: "net",
            label: "Resultado neto",
            amount: formatCurrency(netIncome, currency),
            helper:
                netIncome >= 0
                    ? `Ahorro estimado ${savingsRate.toFixed(0)}%`
                    : `Faltan ${formatCurrency(Math.abs(netIncome), currency)}`,
            trend: netIncome >= 0 ? "En ruta" : "Revísalo",
            trendIsPositive: netIncome >= 0,
        },
    ];

    return (
        <div className="grid gap-4 md:grid-cols-3">
            {stats.map((stat) => (
                <Card
                    key={stat.id}
                    className="border border-white/5 bg-surface/95 p-0 ring-0 backdrop-blur"
                >
                    <CardContent className="space-y-6 px-6 py-6">
                        <div>
                            <p className="text-xs uppercase tracking-[0.3em] text-ink-muted">
                                {stat.label}
                            </p>
                            <p className="mt-3 text-3xl font-semibold text-ink">
                                {stat.amount}
                            </p>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-ink-muted">
                                {stat.helper}
                            </span>
                            <span
                                className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ${
                                    stat.trendIsPositive
                                        ? "bg-success/15 text-success"
                                        : "bg-danger/10 text-danger"
                                }`}
                            >
                                {stat.trendIsPositive ? (
                                    <ArrowUpRight className="h-4 w-4" />
                                ) : (
                                    <ArrowDownRight className="h-4 w-4" />
                                )}
                                {stat.trend}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
};
