import { Link } from "@inertiajs/react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { formatCurrency } from "@/lib/format";

type BudgetProgressCardProps = {
    allocations: App.Data.BudgetAllocationStatusData[];
};

const toNumber = (value: string | number): number => {
    const numericValue = typeof value === "string" ? Number(value) : value;

    return Number.isFinite(numericValue) ? numericValue : 0;
};

export const BudgetProgressCard = ({
    allocations,
}: BudgetProgressCardProps) => {
    const sortedAllocations = [...allocations].sort(
        (a, b) => toNumber(b.spent) - toNumber(a.spent),
    );

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Presupuestos activos</CardTitle>
                    <CardDescription>
                        Controla cuánto has usado por categoría.
                    </CardDescription>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/budgets">Gestionar</Link>
                </Button>
            </CardHeader>
            <CardContent className="space-y-4">
                {sortedAllocations.length === 0 ? (
                    <div className="rounded-[var(--radius-lg)] border border-dashed border-ink/20 px-4 py-6 text-sm text-ink-muted">
                        Aún no tienes presupuestos para este periodo. Crea uno
                        para controlar tus gastos recurrentes.
                    </div>
                ) : (
                    sortedAllocations.map((allocation) => {
                        const budgeted = toNumber(allocation.budgeted);
                        const spent = toNumber(allocation.spent);
                        const remaining = toNumber(allocation.remaining);
                        const percent =
                            budgeted > 0
                                ? Math.min(100, (spent / budgeted) * 100)
                                : 0;
                        const isOverspent = remaining < 0;

                        return (
                            <div
                                key={allocation.allocation_id}
                                className="rounded-[var(--radius-lg)] border border-transparent bg-surface-muted/50 px-4 py-4 shadow-sm transition hover:border-ink/10"
                            >
                                <div className="flex flex-wrap items-center gap-3">
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-semibold text-ink">
                                            {allocation.category_name}
                                        </p>
                                        <p className="text-xs text-ink-muted">
                                            {allocation.budget_name} •{" "}
                                            {allocation.period}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            isOverspent ? "danger" : "info"
                                        }
                                    >
                                        {isOverspent
                                            ? "Sobrepasado"
                                            : "En curso"}
                                    </Badge>
                                </div>
                                <div className="mt-4 space-y-2 text-sm">
                                    <Progress value={percent} />
                                    <div className="flex items-center justify-between text-xs text-ink-muted">
                                        <span>
                                            Gastado{" "}
                                            {formatCurrency(
                                                spent,
                                                allocation.currency_code,
                                            )}
                                        </span>
                                        <span>
                                            Objetivo{" "}
                                            {formatCurrency(
                                                budgeted,
                                                allocation.currency_code,
                                            )}
                                        </span>
                                    </div>
                                    <p className="text-sm font-semibold">
                                        {isOverspent
                                            ? "Te excediste "
                                            : "Disponible "}
                                        {formatCurrency(
                                            Math.abs(remaining),
                                            allocation.currency_code,
                                        )}
                                    </p>
                                </div>
                            </div>
                        );
                    })
                )}
            </CardContent>
        </Card>
    );
};
