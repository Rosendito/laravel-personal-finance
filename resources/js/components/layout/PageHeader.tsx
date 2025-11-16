import type { ReactNode } from "react";

type PageHeaderProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
    children?: ReactNode;
};

export const PageHeader = ({
    eyebrow,
    title,
    description,
    actions,
    children,
}: PageHeaderProps) => {
    return (
        <section className="rounded-lg bg-surface px-6 py-6 ring-1 ring-black/5">
            <div className="flex flex-wrap items-center gap-4">
                <div className="min-w-0 flex-1">
                    {eyebrow ? (
                        <p className="text-xs font-semibold uppercase tracking-[0.4em] text-secondary">
                            {eyebrow}
                        </p>
                    ) : null}
                    <h1 className="text-2xl font-semibold text-ink sm:text-3xl">
                        {title}
                    </h1>
                    {description ? (
                        <p className="mt-2 text-sm text-ink-muted sm:text-base">
                            {description}
                        </p>
                    ) : null}
                </div>
                {actions ? (
                    <div className="flex flex-wrap gap-3">{actions}</div>
                ) : null}
            </div>
            {children ? <div className="mt-6">{children}</div> : null}
        </section>
    );
};
