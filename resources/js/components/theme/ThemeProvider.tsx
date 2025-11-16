import {
    applyTheme,
    readStoredTheme,
    resolveTheme,
    subscribeToSystemTheme,
    type ThemePreference,
} from "@/lib/theme";
import {
    createContext,
    type PropsWithChildren,
    useContext,
    useEffect,
    useMemo,
    useState,
} from "react";

type ThemeContextValue = {
    preference: ThemePreference;
    resolvedTheme: "light" | "dark";
    setPreference: (preference: ThemePreference) => void;
};

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

type ThemeProviderProps = PropsWithChildren<{
    initialPreference?: ThemePreference;
}>;

export const ThemeProvider = ({
    children,
    initialPreference = "system",
}: ThemeProviderProps) => {
    const [preference, setPreference] = useState<ThemePreference>(() => {
        return readStoredTheme() ?? initialPreference;
    });
    const [resolvedTheme, setResolvedTheme] = useState<"light" | "dark">(() =>
        resolveTheme(preference),
    );

    useEffect(() => {
        applyTheme(preference);
        setResolvedTheme(resolveTheme(preference));

        if (preference !== "system") {
            return undefined;
        }

        return subscribeToSystemTheme(setResolvedTheme);
    }, [preference]);

    useEffect(() => {
        if (readStoredTheme() === null && preference !== initialPreference) {
            setPreference(initialPreference);
        }
    }, [initialPreference, preference]);

    const contextValue = useMemo(
        () => ({
            preference,
            resolvedTheme,
            setPreference,
        }),
        [preference, resolvedTheme],
    );

    return (
        <ThemeContext.Provider value={contextValue}>
            {children}
        </ThemeContext.Provider>
    );
};

export const useTheme = (): ThemeContextValue => {
    const context = useContext(ThemeContext);

    if (context === undefined) {
        throw new Error("useTheme must be used within a ThemeProvider");
    }

    return context;
};
