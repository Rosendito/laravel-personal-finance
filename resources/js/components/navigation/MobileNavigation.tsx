import { Link } from "@inertiajs/react";

import { LogoMark } from "@/components/brand/LogoMark";
import { getNavigationIcon } from "@/components/navigation/icons";
import { Sheet, SheetContent } from "@/components/ui/sheet";
import { cn } from "@/lib/utils";
import type { NavigationItem } from "@/types/navigation";

type MobileNavigationProps = {
    appName: string;
    navigation: NavigationItem[];
    activePath: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export const MobileNavigation = ({
    appName,
    navigation,
    activePath,
    open,
    onOpenChange,
}: MobileNavigationProps) => {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="left"
                className="w-80 border-white/10 bg-cream/95"
            >
                <div className="flex items-center gap-3">
                    <LogoMark />
                    <div>
                        <p className="text-base font-semibold text-ink">
                            {appName}
                        </p>
                        <p className="text-xs text-ink-muted">
                            Todo tu dinero en un solo lugar
                        </p>
                    </div>
                </div>

                <div className="mt-8 flex flex-col gap-2">
                    {navigation.map((item) => (
                        <MobileNavLink
                            key={item.id}
                            item={item}
                            activePath={activePath}
                            onNavigate={() => onOpenChange(false)}
                        />
                    ))}
                </div>
            </SheetContent>
        </Sheet>
    );
};

type MobileNavLinkProps = {
    item: NavigationItem;
    activePath: string;
    onNavigate: () => void;
};

const MobileNavLink = ({
    item,
    activePath,
    onNavigate,
}: MobileNavLinkProps) => {
    const Icon = getNavigationIcon(item.icon);
    const isActive =
        item.href === "/"
            ? activePath === "/"
            : activePath.startsWith(item.href) && item.href !== "/";

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            className={cn(
                "flex items-center gap-3 rounded-[20px] border border-transparent px-3 py-3 transition hover:border-ink/10",
                isActive && "border-transparent bg-white shadow-sm",
            )}
        >
            <span
                className={cn(
                    "inline-flex h-10 w-10 items-center justify-center rounded-[18px] bg-ink/5 text-ink",
                    isActive && "bg-primary text-primary-foreground",
                )}
            >
                <Icon className="h-4 w-4" />
            </span>
            <div className="flex flex-col">
                <span
                    className={cn(
                        "font-medium",
                        isActive ? "text-primary-foreground" : "text-ink",
                    )}
                >
                    {item.label}
                </span>
                <span
                    className={cn(
                        "text-xs",
                        isActive
                            ? "text-primary-foreground/70"
                            : "text-ink-muted",
                    )}
                >
                    {item.description}
                </span>
            </div>
        </Link>
    );
};
