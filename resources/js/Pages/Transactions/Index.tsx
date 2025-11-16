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
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from "@/components/ui/select";

const TransactionsIndex = () => {
    return (
        <AppShell>
            <Head title="Transactions" />
            <PageHeader
                eyebrow="Transacciones"
                title="Actividad reciente"
                description="Filtra, busca y concilia movimientos de todas tus cuentas."
                actions={
                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline">Exportar</Button>
                        <Button>Registrar transacción</Button>
                    </div>
                }
            />

            <div className="mt-8 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros rápidos</CardTitle>
                        <CardDescription>
                            Combina filtros para encontrar movimientos
                            específicos.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="search">Buscar</Label>
                            <Input
                                id="search"
                                placeholder="Descripción, referencia, comercio..."
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Cuenta</Label>
                            <Select>
                                <SelectTrigger>
                                    Selecciona una cuenta
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas</SelectItem>
                                    <SelectItem value="checking">
                                        Cuenta principal
                                    </SelectItem>
                                    <SelectItem value="credit">
                                        Tarjeta crédito
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Tipo</Label>
                            <Select>
                                <SelectTrigger>Ingreso o gasto</SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="income">
                                        Ingreso
                                    </SelectItem>
                                    <SelectItem value="expense">
                                        Gasto
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Movimientos</CardTitle>
                        <CardDescription>
                            Una vez conectes tus cuentas podrás ver la tabla de
                            transacciones más recientes aquí.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="rounded-[var(--radius-lg)] border border-dashed border-ink/20 px-4 py-8 text-center text-sm text-ink-muted">
                        Integra una cuenta bancaria o importa un CSV para
                        comenzar a ver tus transacciones. También podrás crear
                        reglas automáticas para clasificar gastos repetitivos.
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
};

export default TransactionsIndex;
