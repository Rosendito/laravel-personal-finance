import { ChevronDown, LogOut, Settings, UserRound } from "lucide-react";

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import type { Viewer } from "@/types/page";

type UserMenuProps = {
    viewer: Viewer;
};

export const UserMenu = ({ viewer }: UserMenuProps) => {
    const initials = viewer?.name
        ? viewer.name
              .split(" ")
              .map((word) => word.charAt(0))
              .join("")
              .slice(0, 2)
              .toUpperCase()
        : "PF";

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="flex items-center gap-3 rounded-[var(--radius-lg)] border border-transparent bg-surface px-2 py-1.5 text-left text-sm shadow-sm ring-1 ring-black/5 transition hover:border-ink/10"
                >
                    <Avatar className="h-10 w-10">
                        <AvatarFallback className="bg-secondary/10 text-secondary">
                            {initials}
                        </AvatarFallback>
                    </Avatar>
                    <div className="hidden min-w-0 flex-col sm:flex">
                        <span className="truncate text-sm font-semibold text-ink">
                            {viewer?.name ?? "Invitado"}
                        </span>
                        <span className="truncate text-xs text-ink-muted">
                            {viewer?.email ?? "Sin sesión activa"}
                        </span>
                    </div>
                    <ChevronDown className="h-4 w-4 text-ink-muted" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>
                    Sesión
                    {viewer?.email && (
                        <p className="text-xs font-normal text-ink-muted">
                            {viewer.email}
                        </p>
                    )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem className="gap-2">
                    <UserRound className="h-4 w-4 text-secondary" />
                    Perfil
                </DropdownMenuItem>
                <DropdownMenuItem className="gap-2">
                    <Settings className="h-4 w-4 text-secondary" />
                    Preferencias
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem className="gap-2 text-danger">
                    <LogOut className="h-4 w-4" />
                    Cerrar sesión
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
};
