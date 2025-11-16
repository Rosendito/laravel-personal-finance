export type ThemePreference = "light" | "dark" | "system";

const STORAGE_KEY = "pf:theme";
const mediaQuery =
    typeof window !== "undefined"
        ? window.matchMedia("(prefers-color-scheme: dark)")
        : null;

const isThemePreference = (value: string | null): value is ThemePreference => {
    return value === "light" || value === "dark" || value === "system";
};

const resolveSystemTheme = (): "light" | "dark" => {
    if (mediaQuery === null) {
        return "light";
    }

    return mediaQuery.matches ? "dark" : "light";
};

export const readStoredTheme = (): ThemePreference | null => {
    if (typeof window === "undefined") {
        return null;
    }

    const value = window.localStorage.getItem(STORAGE_KEY);

    if (value === null) {
        return null;
    }

    return isThemePreference(value) ? value : null;
};

export const resolveTheme = (preference: ThemePreference): "light" | "dark" => {
    if (preference === "system") {
        return resolveSystemTheme();
    }

    return preference;
};

export const applyTheme = (preference: ThemePreference): ThemePreference => {
    if (typeof document === "undefined") {
        return preference;
    }

    const resolved = resolveTheme(preference);
    const root = document.documentElement;

    root.dataset.theme = resolved;
    root.classList.toggle("dark", resolved === "dark");

    if (typeof window !== "undefined") {
        if (preference === "system") {
            window.localStorage.removeItem(STORAGE_KEY);
        } else {
            window.localStorage.setItem(STORAGE_KEY, preference);
        }
    }

    return preference;
};

export const initializeThemePreference = (
    fallback: ThemePreference = "system",
): ThemePreference => {
    if (typeof window === "undefined") {
        return fallback;
    }

    const stored = readStoredTheme() ?? fallback;

    applyTheme(stored);

    return stored;
};

export const subscribeToSystemTheme = (
    callback: (theme: "light" | "dark") => void,
): (() => void) => {
    if (mediaQuery === null) {
        return () => {};
    }

    const handler = (event: MediaQueryListEvent): void => {
        callback(event.matches ? "dark" : "light");
    };

    if (typeof mediaQuery.addEventListener === "function") {
        mediaQuery.addEventListener("change", handler);
    } else {
        mediaQuery.addListener(handler);
    }

    return () => {
        if (typeof mediaQuery.removeEventListener === "function") {
            mediaQuery.removeEventListener("change", handler);
        } else {
            mediaQuery.removeListener(handler);
        }
    };
};
