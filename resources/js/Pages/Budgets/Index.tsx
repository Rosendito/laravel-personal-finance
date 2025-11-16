import { Head, router } from "@inertiajs/react";
import type { FormEvent } from "react";
import { useEffect, useMemo, useState } from "react";

import { AppShell } from "@/components/layout/AppShell";
import { PageHeader } from "@/components/layout/PageHeader";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    BudgetFilters,
    type BudgetFiltersValues,
} from "@/Pages/Budgets/components/BudgetFilters";
import { BudgetFormDialog } from "@/Pages/Budgets/components/BudgetFormDialog";
import { BudgetList } from "@/Pages/Budgets/components/BudgetList";
import { BudgetStats } from "@/Pages/Budgets/components/BudgetStats";
import type { Budget, BudgetsPageProps } from "@/Pages/Budgets/types";

const BudgetsIndex = ({
    budgets,
    stats,
    filters,
    periodOptions,
    defaultPeriod,
}: BudgetsPageProps) => {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editingBudget, setEditingBudget] = useState<Budget | null>(null);
    const [filterForm, setFilterForm] = useState<BudgetFiltersValues>({
        search: filters.search ?? "",
        period: filters.period ?? "",
    });

    useEffect(() => {
        setFilterForm({
            search: filters.search ?? "",
            period: filters.period ?? "",
        });
    }, [filters.search, filters.period]);

    const availablePeriods = useMemo(() => {
        const options = [...periodOptions];

        if (options.length === 0 && defaultPeriod) {
            options.push(defaultPeriod);
        } else if (defaultPeriod && !options.includes(defaultPeriod)) {
            options.unshift(defaultPeriod);
        }

        return Array.from(new Set(options));
    }, [periodOptions, defaultPeriod]);

    const handleFilterSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            "/budgets",
            {
                search: filterForm.search || undefined,
                period: filterForm.period || undefined,
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const handleResetFilters = () => {
        setFilterForm({ search: "", period: "" });
        router.get(
            "/budgets",
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const handleDelete = (budget: Budget) => {
        if (
            !confirm(
                `¿Seguro que deseas eliminar el presupuesto “${budget.name}”?`,
            )
        ) {
            return;
        }

        router.delete(`/budgets/${budget.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppShell>
            <Head title="Budgets" />
            <PageHeader
                eyebrow="Presupuestos"
                title="Planifica tus categorías"
                description="Crea presupuestos mensuales, actualízalos en segundos y compara periodos sin salir de esta vista."
                actions={
                    <Button onClick={() => setCreateModalOpen(true)}>
                        Nuevo presupuesto
                    </Button>
                }
            />

            <div className="mt-8 space-y-6">
                <BudgetStats stats={stats} />

                <Card>
                    <CardHeader className="gap-4 lg:flex lg:items-end lg:justify-between">
                        <div>
                            <CardTitle>Presupuestos activos</CardTitle>
                            <CardDescription>
                                Filtra por periodo o nombre y edita desde la
                                misma tabla usando los modales.
                            </CardDescription>
                        </div>
                        <BudgetFilters
                            values={filterForm}
                            availablePeriods={availablePeriods}
                            onSubmit={handleFilterSubmit}
                            onReset={handleResetFilters}
                            onChange={(values) => setFilterForm(values)}
                        />
                    </CardHeader>
                    <CardContent>
                        <BudgetList
                            budgets={budgets}
                            onEdit={setEditingBudget}
                            onDelete={handleDelete}
                        />
                    </CardContent>
                </Card>

                <Card className="bg-surface-muted/60">
                    <CardHeader>
                        <CardTitle>Tips para periodos</CardTitle>
                        <CardDescription>
                            Usa un único modal para crear o editar y mantén los
                            mismos campos para disminuir errores.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-ink-muted">
                        <p>
                            - Define periodos YYYY-MM (ej. 2025-02) para poder
                            duplicar rápidamente hacia los siguientes meses.
                        </p>
                        <p>
                            - Activa o archiva según tu flujo: puedes clonar un
                            presupuesto archivado usando el modal de edición.
                        </p>
                        <p>
                            - Si trabajas con varias monedas agrega el sufijo en
                            el nombre, por ejemplo “Renta · USD”.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <BudgetFormDialog
                mode="create"
                open={createModalOpen}
                onOpenChange={(open) => setCreateModalOpen(open)}
                defaultPeriod={filterForm.period || defaultPeriod}
            />

            <BudgetFormDialog
                mode="edit"
                budget={editingBudget}
                open={editingBudget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingBudget(null);
                    }
                }}
                defaultPeriod={editingBudget?.period ?? defaultPeriod}
            />
        </AppShell>
    );
};

export default BudgetsIndex;
