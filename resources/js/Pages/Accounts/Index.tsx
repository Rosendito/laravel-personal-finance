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

const AccountsIndex = () => {
    return (
        <AppShell>
            <Head title="Accounts" />
            <PageHeader
                eyebrow="Cuentas"
                title="Tus cuentas conectadas"
                description="Consolida bancos, tarjetas y wallets en una sola vista para hacer seguimiento diario."
                actions={
                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline">Importar estado</Button>
                        <Button>Agregar cuenta</Button>
                    </div>
                }
            />

            <div className="mt-8 grid gap-6 lg:grid-cols-[2fr,1fr]">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <CardTitle>Listado de cuentas</CardTitle>
                            <CardDescription>
                                Filtra por banco o tipo para editar saldos.
                            </CardDescription>
                        </div>
                        <div className="min-w-[220px]">
                            <Label htmlFor="account-search" className="sr-only">
                                Buscar
                            </Label>
                            <Input
                                id="account-search"
                                placeholder="Buscar por institución"
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-ink-muted">
                        <p>
                            Estamos listos para sincronizar tus cuentas. Una vez
                            conectadas podrás ver movimientos, moneda y saldos
                            actualizados automáticamente.
                        </p>
                        <div className="rounded-[var(--radius-lg)] border border-dashed border-ink/20 px-4 py-5">
                            <p className="font-semibold text-ink">
                                ¿Qué sigue?
                            </p>
                            <ol className="mt-3 list-decimal space-y-2 pl-4">
                                <li>Configura tu primer banco o tarjeta.</li>
                                <li>Define la moneda y saldo inicial.</li>
                                <li>
                                    Usa la tabla para editar o archivar cuentas.
                                </li>
                            </ol>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-surface-muted/60">
                    <CardHeader>
                        <CardTitle>Consejo rápido</CardTitle>
                        <CardDescription>
                            Nombra las cuentas con un prefijo corto (por
                            ejemplo, &ldquo;MX&rdquo; o &ldquo;USD&rdquo;) para
                            identificarlas mejor dentro del dashboard.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="text-sm text-ink-muted">
                        También puedes agrupar cuentas similares (e.g. tarjetas
                        corporativas) usando colores o emojis en el nombre para
                        ubicarlas más rápido.
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
};

export default AccountsIndex;
