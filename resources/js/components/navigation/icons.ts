import type { LucideIcon } from "lucide-react";
import {
    ArrowLeftRight,
    LayoutDashboard,
    PiggyBank,
    Wallet2,
} from "lucide-react";

import type { NavigationIconName } from "@/types/navigation";

const iconMap: Record<NavigationIconName, LucideIcon> = {
    "layout-dashboard": LayoutDashboard,
    wallet: Wallet2,
    "arrows-left-right": ArrowLeftRight,
    "piggy-bank": PiggyBank,
};

export const getNavigationIcon = (name: NavigationIconName): LucideIcon =>
    iconMap[name];
