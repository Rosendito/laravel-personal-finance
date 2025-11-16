import type { FormEvent } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from "@/components/ui/select";
import { formatPeriod } from "@/Pages/Budgets/utils";

export type BudgetFiltersValues = {
    search: string;
    period: string;
};

type BudgetFiltersProps = {
    values: BudgetFiltersValues;
    availablePeriods: string[];
    onChange: (values: BudgetFiltersValues) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onReset: () => void;
};

const ALL_PERIODS_VALUE = "__all_periods__";

export const BudgetFilters = ({
    values,
    availablePeriods,
    onChange,
    onSubmit,
    onReset,
}: BudgetFiltersProps) => {
    const handleSearchChange = (value: string) => {
        onChange({
            ...values,
            search: value,
        });
    };

    const handlePeriodChange = (value: string) => {
        onChange({
            ...values,
            period: value,
        });
    };

    return (
        <form
            className="grid gap-3 sm:grid-cols-2 lg:w-[420px]"
            onSubmit={onSubmit}
        >
            <div className="space-y-2">
                <Label htmlFor="budget-search">Buscar</Label>
                <Input
                    id="budget-search"
                    placeholder="Supermercado, renta..."
                    value={values.search}
                    onChange={(event) => handleSearchChange(event.target.value)}
                />
            </div>
            <div className="space-y-2">
                <Label>Periodo</Label>
                <Select
                    value={values.period || ALL_PERIODS_VALUE}
                    onValueChange={(value) =>
                        handlePeriodChange(
                            value === ALL_PERIODS_VALUE ? "" : value,
                        )
                    }
                >
                    <SelectTrigger>
                        {values.period
                            ? formatPeriod(values.period)
                            : "Todos los periodos"}
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL_PERIODS_VALUE}>
                            Todos los periodos
                        </SelectItem>
                        {availablePeriods.map((period) => (
                            <SelectItem key={period} value={period}>
                                {formatPeriod(period)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" className="flex-1" variant="secondary">
                    Aplicar
                </Button>
                <Button type="button" variant="outline" onClick={onReset}>
                    Limpiar
                </Button>
            </div>
        </form>
    );
};
