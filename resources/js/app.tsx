import "./bootstrap/index";

import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { StrictMode } from "react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import { ThemeProvider } from "@/components/theme/ThemeProvider";
import type { PageProps } from "@/types/page";

const defaultTitle =
    document.getElementsByTagName("title")[0]?.innerText ?? "Personal Finance";

createInertiaApp({
    title: (title) => (title ? `${title} · ${defaultTitle}` : defaultTitle),
    progress: {
        color: "#4A86FF",
        showSpinner: false,
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob("./Pages/**/*.tsx"),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        const { initialTheme = "system" } = props.initialPage
            .props as PageProps;

        root.render(
            <StrictMode>
                <ThemeProvider initialPreference={initialTheme}>
                    <App {...props} />
                </ThemeProvider>
            </StrictMode>,
        );
    },
});
