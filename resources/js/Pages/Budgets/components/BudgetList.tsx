import { Edit3, Trash2 } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { Budget } from "@/Pages/Budgets/types";
import { formatPeriod, formatShortDate } from "@/Pages/Budgets/utils";

type BudgetListProps = {
    budgets: Budget[];
    onEdit: (budget: Budget) => void;
    onDelete: (budget: Budget) => void;
};

export const BudgetList = ({ budgets, onEdit, onDelete }: BudgetListProps) => {
    if (budgets.length === 0) {
        return (
            <div className="rounded-[var(--radius-lg)] border border-dashed border-ink/20 px-4 py-8 text-center text-sm text-ink-muted">
                Aún no tienes presupuestos para este periodo. Crea el primero
                para comenzar a monitorear tus límites mensuales.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-[var(--radius-lg)] border border-ink/10">
            <div className="hidden grid-cols-[2fr,1fr,1fr,1fr,auto] bg-surface-muted/70 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-ink-muted md:grid">
                <span>Nombre</span>
                <span>Periodo</span>
                <span>Estado</span>
                <span>Actualizado</span>
                <span className="text-right">Acciones</span>
            </div>
            <div className="divide-y divide-ink/10">
                {budgets.map((budget) => (
                    <div
                        key={budget.id}
                        className="grid grid-cols-1 gap-4 px-4 py-4 text-sm md:grid-cols-[2fr,1fr,1fr,1fr,auto] md:items-center"
                    >
                        <div>
                            <p className="font-semibold text-ink">
                                {budget.name}
                            </p>
                            <p className="text-xs text-ink-muted md:hidden">
                                {formatPeriod(budget.period)}
                            </p>
                        </div>
                        <div className="hidden md:block">
                            {formatPeriod(budget.period)}
                        </div>
                        <div>
                            <Badge
                                variant={
                                    budget.is_active ? "success" : "default"
                                }
                            >
                                {budget.is_active ? "Activo" : "Archivado"}
                            </Badge>
                        </div>
                        <div className="text-xs text-ink-muted">
                            {formatShortDate(budget.updated_at)}
                        </div>
                        <div className="flex items-center gap-2 md:justify-end">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => onEdit(budget)}
                            >
                                <Edit3 className="h-4 w-4" />
                                <span className="sr-only">
                                    Editar presupuesto
                                </span>
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="text-danger hover:text-danger"
                                onClick={() => onDelete(budget)}
                            >
                                <Trash2 className="h-4 w-4" />
                                <span className="sr-only">
                                    Eliminar presupuesto
                                </span>
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};
