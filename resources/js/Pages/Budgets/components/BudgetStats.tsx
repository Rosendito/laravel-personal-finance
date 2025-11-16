import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import type { BudgetStatsSummary } from "@/Pages/Budgets/types";

type BudgetStatsProps = {
    stats: BudgetStatsSummary;
};

const itemsConfig = [
    {
        label: "Total de presupuestos",
        helper: "Incluye activos y archivados",
        key: "total" as const,
    },
    {
        label: "Activos",
        helper: "Impactan tu tablero",
        key: "active" as const,
    },
    {
        label: "Archivados",
        helper: "Guardados como referencia",
        key: "inactive" as const,
    },
];

export const BudgetStats = ({ stats }: BudgetStatsProps) => {
    return (
        <div className="grid gap-4 md:grid-cols-3">
            {itemsConfig.map((item) => (
                <Card key={item.key}>
                    <CardHeader>
                        <CardDescription>{item.label}</CardDescription>
                        <CardTitle className="text-3xl">
                            {stats[item.key]}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-ink-muted">
                        {item.helper}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
};
