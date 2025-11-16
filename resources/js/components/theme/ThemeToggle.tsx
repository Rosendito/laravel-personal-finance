import { MonitorSmartphone, Moon, Sun } from "lucide-react";

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { ThemePreference } from "@/lib/theme";
import { useTheme } from "@/components/theme/ThemeProvider";

const options: Array<{
    icon: typeof Sun;
    label: string;
    helper: string;
    value: ThemePreference;
}> = [
    {
        value: "light",
        label: "Modo claro",
        helper: "Ideal para espacios iluminados",
        icon: Sun,
    },
    {
        value: "dark",
        label: "Modo oscuro",
        helper: "Reduce el brillo en la noche",
        icon: Moon,
    },
    {
        value: "system",
        label: "Usar sistema",
        helper: "Se adapta automáticamente",
        icon: MonitorSmartphone,
    },
];

export const ThemeToggle = () => {
    const { preference, resolvedTheme, setPreference } = useTheme();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-11 w-11 rounded-[var(--radius-lg)] bg-surface text-ink shadow-sm ring-1 ring-black/5 transition hover:text-secondary"
                >
                    {resolvedTheme === "dark" ? (
                        <Moon className="h-5 w-5" />
                    ) : (
                        <Sun className="h-5 w-5" />
                    )}
                    <span className="sr-only">Cambiar tema</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
                <DropdownMenuLabel>Preferencia de tema</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {options.map((option) => {
                    const Icon = option.icon;
                    const isActive = preference === option.value;

                    return (
                        <DropdownMenuItem
                            key={option.value}
                            onSelect={() => setPreference(option.value)}
                            className={cn(
                                "flex flex-col items-start gap-1 rounded-[var(--radius-md)] px-3 py-3 text-sm",
                                isActive && "bg-surface-muted",
                            )}
                        >
                            <div className="flex w-full items-center gap-2 font-medium">
                                <Icon className="h-4 w-4 text-secondary" />
                                {option.label}
                                {isActive && (
                                    <span className="ml-auto text-xs uppercase tracking-wide text-secondary">
                                        Activo
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-ink-muted">
                                {option.helper}
                            </p>
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
};
