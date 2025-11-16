export type NavigationIconName =
    | "layout-dashboard"
    | "wallet"
    | "arrows-left-right"
    | "piggy-bank";

export type NavigationItem = {
    id: string;
    label: string;
    description: string;
    href: string;
    icon: NavigationIconName;
};
