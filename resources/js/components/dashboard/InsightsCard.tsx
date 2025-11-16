import { Lightbulb, TrendingDown, TrendingUp } from "lucide-react";

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatCurrency } from "@/lib/format";

type InsightsCardProps = {
    allocations: App.Data.BudgetAllocationStatusData[];
    summary: App.Data.IncomeStatementSummaryData;
};

const toNumber = (value: string | number): number => {
    const numericValue = typeof value === "string" ? Number(value) : value;

    return Number.isFinite(numericValue) ? numericValue : 0;
};

export const InsightsCard = ({ allocations, summary }: InsightsCardProps) => {
    const totalBudgeted = allocations.reduce(
        (acc, allocation) => acc + toNumber(allocation.budgeted),
        0,
    );
    const totalSpent = allocations.reduce(
        (acc, allocation) => acc + toNumber(allocation.spent),
        0,
    );
    const totalRemaining = allocations.reduce(
        (acc, allocation) => acc + toNumber(allocation.remaining),
        0,
    );
    const netIncome = toNumber(summary.net_income);

    const overspentCategory = allocations.find(
        (allocation) => toNumber(allocation.remaining) < 0,
    );
    const healthiestCategory = allocations.reduce((best, current) => {
        const currentRemaining = toNumber(current.remaining);
        const bestRemaining = best
            ? toNumber(best.remaining)
            : Number.NEGATIVE_INFINITY;

        if (currentRemaining > bestRemaining) {
            return current;
        }

        return best;
    }, allocations[0]);

    const items = [
        {
            id: "net",
            title: netIncome >= 0 ? "Vas con superávit" : "Revisa tus gastos",
            description:
                netIncome >= 0
                    ? "Tu flujo mensual sigue siendo positivo, puedes enviar más a tu fondo de seguridad."
                    : "Recorta gastos variables para equilibrar el mes y evitar usar ahorros.",
            badge: netIncome >= 0 ? "Saludable" : "Atención",
            badgeVariant: netIncome >= 0 ? "success" : "danger",
            icon: netIncome >= 0 ? TrendingUp : TrendingDown,
        },
        overspentCategory && {
            id: "overspent",
            title: `${overspentCategory.category_name} supera el plan`,
            description: `Has usado más de lo previsto en ${
                overspentCategory.category_name
            }. Ajusta a ${formatCurrency(
                toNumber(overspentCategory.budgeted),
                overspentCategory.currency_code,
            )} o redistribuye desde otra categoría.`,
            badge: "Prioridad",
            badgeVariant: "danger",
            icon: TrendingDown,
        },
        healthiestCategory && {
            id: "healthy",
            title: `${healthiestCategory.category_name} sigue controlada`,
            description: `Quedan ${formatCurrency(
                Math.max(0, toNumber(healthiestCategory.remaining)),
                healthiestCategory.currency_code,
            )} disponibles. Considera mover parte a objetivos con más presión.`,
            badge: "A favor",
            badgeVariant: "info",
            icon: TrendingUp,
        },
        totalBudgeted > 0 && {
            id: "coverage",
            title: "Uso del presupuesto",
            description: `Has usado ${Math.round((totalSpent / totalBudgeted) * 100)}% del presupuesto del periodo. ${
                totalRemaining > 0
                    ? "Aún tienes margen para categorías pendientes."
                    : "Haz ajustes hoy mismo."
            }`,
            badge: "Resumen",
            badgeVariant: totalRemaining > 0 ? "info" : "warning",
            icon: Lightbulb,
        },
    ].filter(Boolean) as Array<{
        id: string;
        title: string;
        description: string;
        badge: string;
        badgeVariant: "success" | "danger" | "info" | "warning";
        icon: typeof Lightbulb;
    }>;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Insights rápidos</CardTitle>
                <CardDescription>
                    Sugerencias automáticas según tu actividad.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {items.length === 0 ? (
                    <div className="text-sm text-ink-muted">
                        Necesitamos más datos para mostrar recomendaciones.
                        Registra ingresos, gastos y presupuestos para generar
                        insights útiles.
                    </div>
                ) : (
                    items.map((item) => {
                        const Icon = item.icon;

                        return (
                            <div
                                key={item.id}
                                className="rounded-[var(--radius-lg)] border border-transparent bg-surface-muted/60 px-4 py-4 shadow-sm"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div className="flex items-center gap-3">
                                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-secondary shadow-sm">
                                            <Icon className="h-5 w-5" />
                                        </span>
                                        <div>
                                            <p className="font-semibold text-ink">
                                                {item.title}
                                            </p>
                                            <p className="text-sm text-ink-muted">
                                                {item.description}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge variant={item.badgeVariant}>
                                        {item.badge}
                                    </Badge>
                                </div>
                            </div>
                        );
                    })
                )}
            </CardContent>
        </Card>
    );
};
