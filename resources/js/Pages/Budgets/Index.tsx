import { Head } from "@inertiajs/react";

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
import { Progress } from "@/components/ui/progress";

const BudgetsIndex = () => {
    return (
        <AppShell>
            <Head title="Budgets" />
            <PageHeader
                eyebrow="Presupuestos"
                title="Planifica tus categorías"
                description="Define montos por categoría, rastrea el consumo y recibe alertas antes de excederte."
                actions={
                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline">Duplicar periodo</Button>
                        <Button>Nuevo presupuesto</Button>
                    </div>
                }
            />

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Resumen del periodo</CardTitle>
                        <CardDescription>
                            Configura montos objetivo mensuales para tener
                            visibilidad de tus límites.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-ink-muted">
                        <p>
                            Cuando agregues presupuestos los verás aquí
                            organizados por categoría. También podrás
                            duplicarlos para futuros periodos y simular
                            escenarios.
                        </p>
                        <div className="rounded-[var(--radius-lg)] bg-surface-muted/80 px-4 py-5">
                            <p className="text-xs uppercase tracking-[0.3em] text-secondary">
                                Ejemplo
                            </p>
                            <p className="mt-2 text-base font-semibold text-ink">
                                Gastos fijos
                            </p>
                            <Progress value={45} className="mt-4" />
                            <div className="mt-2 flex items-center justify-between text-xs">
                                <span>450 USD gastados</span>
                                <span>1,000 USD objetivo</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="bg-surface-muted/50">
                    <CardHeader>
                        <CardTitle>Sugerencias</CardTitle>
                        <CardDescription>
                            Usa categorías cortas y específicas para leer tu
                            tablero sin esfuerzo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-ink-muted">
                        <p>Al crear un presupuesto define:</p>
                        <ul className="list-disc space-y-2 pl-5">
                            <li>
                                Nombre claro: &ldquo;Supermercado&rdquo;,
                                &ldquo;Suscripciones&rdquo;, etc.
                            </li>
                            <li>
                                Límite mensual: ajusta según tu promedio real.
                            </li>
                            <li>Objetivo: marca si es esencial o ahorro.</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
};

export default BudgetsIndex;
