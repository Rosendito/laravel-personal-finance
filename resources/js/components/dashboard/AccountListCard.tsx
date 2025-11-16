import { Link } from "@inertiajs/react";
import { ArrowUpRight } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { formatCurrency } from "@/lib/format";

type AccountListCardProps = {
    accounts: App.Data.AccountBalanceData[];
};

export const AccountListCard = ({ accounts }: AccountListCardProps) => {
    const visibleAccounts = accounts.slice(0, 5);

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Cuentas</CardTitle>
                    <CardDescription>
                        Saldo disponible por cuenta conectada.
                    </CardDescription>
                </div>
                <Button variant="ghost" size="sm" className="gap-2" asChild>
                    <Link href="/accounts">
                        Ver todo
                        <ArrowUpRight className="h-4 w-4" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent className="space-y-4">
                {visibleAccounts.length === 0 ? (
                    <div className="rounded-(--radius-lg) bg-surface-muted/60 px-4 py-6 text-sm text-ink-muted">
                        Aún no tienes cuentas sincronizadas. Agrega una para
                        empezar a seguir tus saldos diarios.
                    </div>
                ) : (
                    <ul className="space-y-3">
                        {visibleAccounts.map((account) => (
                            <li
                                key={account.account_id}
                                className="flex items-center justify-between rounded-(--radius-lg) border border-transparent bg-surface-muted/50 px-4 py-3 shadow-sm"
                            >
                                <div>
                                    <p className="text-sm font-semibold text-ink">
                                        {account.name}
                                    </p>
                                    <p className="text-xs text-ink-muted">
                                        {account.currency_code} • #
                                        {account.account_id}
                                    </p>
                                </div>
                                <p className="text-base font-semibold text-ink">
                                    {formatCurrency(
                                        account.balance,
                                        account.currency_code,
                                    )}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
};
