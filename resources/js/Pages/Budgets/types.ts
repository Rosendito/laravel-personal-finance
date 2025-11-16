import type { PageProps } from "@/types/page";

export type Budget = {
    id: number;
    name: string;
    period: string;
    is_active: boolean;
    created_at?: string | null;
    updated_at?: string | null;
};

export type BudgetStatsSummary = {
    total: number;
    active: number;
    inactive: number;
};

export type BudgetFiltersState = {
    search: string | null;
    period: string | null;
};

export type BudgetsPageProps = PageProps<{
    budgets: Budget[];
    stats: BudgetStatsSummary;
    filters: BudgetFiltersState;
    periodOptions: string[];
    defaultPeriod: string;
}>;

export type BudgetFormData = {
    name: string;
    period: string;
    is_active: boolean;
};

