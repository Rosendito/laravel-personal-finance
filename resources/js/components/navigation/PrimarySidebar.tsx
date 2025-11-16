import { Link } from "@inertiajs/react";
import { ArrowUpRight } from "lucide-react";

import { LogoMark } from "@/components/brand/LogoMark";
import { getNavigationIcon } from "@/components/navigation/icons";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { NavigationItem } from "@/types/navigation";

type PrimarySidebarProps = {
    appName: string;
    navigation: NavigationItem[];
    activePath: string;
};

export const PrimarySidebar = ({
    appName,
    navigation,
    activePath,
}: PrimarySidebarProps) => {
    return (
        <aside className="hidden w-72 flex-col border-r border-transparent bg-cream/95 px-6 py-8 backdrop-blur-lg dark:border-white/10 dark:bg-ink/20 md:sticky md:top-0 md:flex md:h-screen md:shrink-0 md:overflow-y-auto">
            <Link href="/" className="flex items-center gap-3">
                <LogoMark />
                <div>
                    <p className="text-base font-semibold text-ink">
                        {appName}
                    </p>
                    <p className="text-xs text-ink-muted">
                        Tu panel financiero diario
                    </p>
                </div>
            </Link>

            <nav className="mt-10 flex flex-col gap-2">
                {navigation.map((item) => (
                    <SidebarLink
                        key={item.id}
                        item={item}
                        activePath={activePath}
                    />
                ))}
            </nav>

            <div className="mt-auto rounded-[var(--radius-lg)] bg-surface px-4 py-5 text-sm shadow-sm ring-1 ring-black/5">
                <p className="text-xs uppercase tracking-[0.25rem] text-secondary">
                    Tip
                </p>
                <p className="mt-3 font-semibold text-ink">
                    Sincroniza tus datos
                </p>
                <p className="mt-1 text-sm text-ink-muted">
                    Importa movimientos bancarios para tener una visión real
                    diaria.
                </p>
                <Button
                    variant="secondary"
                    size="sm"
                    className="mt-4 w-full justify-between text-sm"
                    asChild
                >
                    <Link href="/transactions">
                        Revisar actividad
                        <ArrowUpRight className="h-4 w-4" />
                    </Link>
                </Button>
            </div>
        </aside>
    );
};

type SidebarLinkProps = {
    item: NavigationItem;
    activePath: string;
};

const SidebarLink = ({ item, activePath }: SidebarLinkProps) => {
    const Icon = getNavigationIcon(item.icon);
    const isActive =
        item.href === "/"
            ? activePath === "/"
            : activePath.startsWith(item.href) && item.href !== "/";

    return (
        <Link
            href={item.href}
            className={cn(
                "group flex flex-col gap-1 rounded-[24px] border border-transparent px-4 py-3 transition hover:border-ink/10 hover:bg-white/60",
                isActive && "border-transparent bg-white shadow-sm",
            )}
        >
            <div className="flex items-center gap-3">
                <span
                    className={cn(
                        "inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-ink/5 text-ink transition group-hover:bg-primary group-hover:text-primary-foreground",
                        isActive && "bg-primary text-primary-foreground",
                    )}
                >
                    <Icon className="h-4 w-4" />
                </span>
                <div>
                    <p
                        className={cn(
                            "text-sm font-semibold",
                            isActive ? "text-primary-foreground" : "text-ink",
                        )}
                    >
                        {item.label}
                    </p>
                    <p
                        className={cn(
                            "text-xs",
                            isActive
                                ? "text-primary-foreground/70"
                                : "text-ink-muted",
                        )}
                    >
                        {item.description}
                    </p>
                </div>
            </div>
        </Link>
    );
};
