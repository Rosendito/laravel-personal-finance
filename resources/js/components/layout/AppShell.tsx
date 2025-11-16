import type { ReactNode } from "react";
import { useEffect, useState } from "react";
import { Menu, Search } from "lucide-react";
import { usePage } from "@inertiajs/react";

import { CommandMenu } from "@/components/navigation/CommandMenu";
import { MobileNavigation } from "@/components/navigation/MobileNavigation";
import { PrimarySidebar } from "@/components/navigation/PrimarySidebar";
import { ThemeToggle } from "@/components/theme/ThemeToggle";
import { UserMenu } from "@/components/navigation/UserMenu";
import { Button } from "@/components/ui/button";
import type { PageProps } from "@/types/page";

type AppShellProps = {
    children: ReactNode;
};

export const AppShell = ({ children }: AppShellProps) => {
    const { props, url } = usePage<PageProps>();
    const [isCommandOpen, setIsCommandOpen] = useState(false);
    const [isMobileNavOpen, setIsMobileNavOpen] = useState(false);
    const activePath = url.split("?")[0] ?? "/";

    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === "k"
            ) {
                event.preventDefault();
                setIsCommandOpen((previous) => !previous);
            }
        };

        window.addEventListener("keydown", handler);

        return () => window.removeEventListener("keydown", handler);
    }, []);

    return (
        <>
            <CommandMenu
                navigation={props.primaryNavigation}
                open={isCommandOpen}
                onOpenChange={setIsCommandOpen}
            />
            <MobileNavigation
                appName={props.appName}
                navigation={props.primaryNavigation}
                activePath={activePath}
                open={isMobileNavOpen}
                onOpenChange={setIsMobileNavOpen}
            />
            <div className="app-shell bg-cream text-ink">
                <PrimarySidebar
                    appName={props.appName}
                    navigation={props.primaryNavigation}
                    activePath={activePath}
                />
                <div className="flex min-h-screen flex-1 flex-col border-l border-transparent bg-cream/95 dark:border-white/10 dark:bg-ink/10">
                    <header className="sticky top-0 z-10 border-b border-transparent bg-surface/95 px-4 py-3 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-surface/30 sm:px-6">
                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="md:hidden"
                                onClick={() => setIsMobileNavOpen(true)}
                            >
                                <Menu className="h-5 w-5" />
                                <span className="sr-only">
                                    Abrir navegación
                                </span>
                            </Button>

                            <button
                                type="button"
                                onClick={() => setIsCommandOpen(true)}
                                className="flex flex-1 items-center justify-between gap-3 rounded-(--radius-lg) border border-transparent bg-surface px-4 py-3 text-left text-sm text-ink-muted shadow-sm ring-1 ring-black/5 transition hover:border-secondary/30"
                            >
                                <span className="flex items-center gap-3">
                                    <Search className="h-4 w-4 text-ink-muted" />
                                    Busca rápido o usa acciones (⌘K)
                                </span>
                                <kbd className="hidden rounded-lg border border-ink/10 px-2 py-1 text-xs uppercase tracking-widest text-ink-muted sm:inline-flex">
                                    ⌘ K
                                </kbd>
                            </button>

                            <ThemeToggle />
                            <UserMenu viewer={props.viewer} />
                        </div>
                    </header>

                    <main className="flex-1 rounded-3xl bg-white/80 px-4 pb-10 pt-6 shadow-lg ring-1 ring-black/5 transition dark:rounded-none dark:bg-transparent dark:shadow-none dark:ring-0 sm:px-6 lg:px-10">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
};
