import { router } from "@inertiajs/react";

import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { getNavigationIcon } from "@/components/navigation/icons";
import type { NavigationItem } from "@/types/navigation";

type CommandMenuProps = {
    navigation: NavigationItem[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export const CommandMenu = ({
    navigation,
    open,
    onOpenChange,
}: CommandMenuProps) => {
    const handleNavigate = (href: string) => {
        onOpenChange(false);
        router.visit(href);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-xl overflow-hidden border-0 bg-transparent p-0 shadow-none">
                <Command className="border border-black/5 bg-surface shadow-xl dark:border-white/10">
                    <CommandInput placeholder="Buscar vistas o acciones..." />
                    <CommandList>
                        <CommandEmpty>Sin coincidencias todavía.</CommandEmpty>
                        <CommandGroup heading="Navegación principal">
                            {navigation.map((item) => {
                                const Icon = getNavigationIcon(item.icon);

                                return (
                                    <CommandItem
                                        key={item.id}
                                        value={`${item.label} ${item.description}`}
                                        onSelect={() =>
                                            handleNavigate(item.href)
                                        }
                                        className="gap-3"
                                    >
                                        <Icon className="h-4 w-4 text-secondary" />
                                        <div className="flex flex-col items-start">
                                            <span className="font-medium text-ink">
                                                {item.label}
                                            </span>
                                            <span className="text-xs text-ink-muted">
                                                {item.description}
                                            </span>
                                        </div>
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </DialogContent>
        </Dialog>
    );
};
