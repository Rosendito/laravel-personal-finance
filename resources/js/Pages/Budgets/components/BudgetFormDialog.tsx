import { useForm } from "@inertiajs/react";
import type { FormEvent } from "react";
import { useEffect } from "react";

import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from "@/components/ui/select";
import type { Budget, BudgetFormData } from "@/Pages/Budgets/types";

type BudgetFormDialogProps = {
    mode: "create" | "edit";
    open: boolean;
    onOpenChange: (open: boolean) => void;
    budget?: Budget | null;
    defaultPeriod: string;
};

export const BudgetFormDialog = ({
    mode,
    open,
    onOpenChange,
    budget,
    defaultPeriod,
}: BudgetFormDialogProps) => {
    const isEditing = mode === "edit" && Boolean(budget);
    const {
        data,
        setData,
        setDefaults,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm<BudgetFormData>({
        name: budget?.name ?? "",
        period: budget?.period ?? defaultPeriod,
        is_active: budget?.is_active ?? true,
    });

    useEffect(() => {
        if (!open) {
            return;
        }

        const defaults: BudgetFormData = {
            name: budget?.name ?? "",
            period: budget?.period ?? defaultPeriod,
            is_active: budget?.is_active ?? true,
        };

        setDefaults(defaults);
        setData(defaults);
        clearErrors();
    }, [open, budget, defaultPeriod, setData, setDefaults, clearErrors]);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const actionOptions = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (isEditing && budget) {
            put(`/budgets/${budget.id}`, actionOptions);

            return;
        }

        post("/budgets", actionOptions);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing
                            ? `Editar ${budget?.name ?? "presupuesto"}`
                            : "Nuevo presupuesto"}
                    </DialogTitle>
                    <DialogDescription>
                        Usa los mismos campos para crear o actualizar. Las
                        validaciones se manejan en el backend y las verás aquí.
                    </DialogDescription>
                </DialogHeader>

                <form className="space-y-5" onSubmit={handleSubmit}>
                    <div className="space-y-2">
                        <Label htmlFor={`budget-name-${mode}`}>Nombre</Label>
                        <Input
                            id={`budget-name-${mode}`}
                            placeholder="Ej. Supermercado"
                            value={data.name}
                            onChange={(event) =>
                                setData("name", event.target.value)
                            }
                            required
                        />
                        {errors.name && (
                            <p className="text-sm text-danger">{errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor={`budget-period-${mode}`}>Periodo</Label>
                        <Input
                            id={`budget-period-${mode}`}
                            type="month"
                            value={data.period}
                            onChange={(event) =>
                                setData("period", event.target.value)
                            }
                            min="2000-01"
                            required
                        />
                        {errors.period && (
                            <p className="text-sm text-danger">
                                {errors.period}
                            </p>
                        )}
                        <p className="text-xs text-ink-muted">
                            Formato YYYY-MM para alinear con tus reportes.
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label>Estado</Label>
                        <Select
                            value={data.is_active ? "active" : "inactive"}
                            onValueChange={(value) =>
                                setData("is_active", value === "active")
                            }
                        >
                            <SelectTrigger>
                                {data.is_active ? "Activo" : "Archivado"}
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Activo</SelectItem>
                                <SelectItem value="inactive">
                                    Archivado
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.is_active && (
                            <p className="text-sm text-danger">
                                {errors.is_active}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {isEditing ? "Guardar cambios" : "Crear"}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
};
