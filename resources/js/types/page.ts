import type { ThemePreference } from "@/lib/theme";
import type { NavigationItem } from "@/types/navigation";

export type Viewer = {
    name: string;
    email?: string | null;
} | null;

export type SharedPageProps = {
    appName: string;
    initialTheme: ThemePreference;
    primaryNavigation: NavigationItem[];
    viewer: Viewer;
};

export type PageProps<TPayload = Record<string, never>> = SharedPageProps &
    TPayload;
