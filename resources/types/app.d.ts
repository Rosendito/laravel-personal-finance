import type { PageProps as ApplicationPageProps } from "@/types/page";

declare module "@inertiajs/core" {
    interface PageProps extends ApplicationPageProps {}
}
