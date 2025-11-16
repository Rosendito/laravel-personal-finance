import * as React from "react";

import { cn } from "@/lib/utils";

export type ProgressProps = React.HTMLAttributes<HTMLDivElement> & {
    value?: number;
};

export const Progress = React.forwardRef<HTMLDivElement, ProgressProps>(
    ({ className, value = 0, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                "relative h-3 w-full overflow-hidden rounded-[var(--radius-pill)] bg-ink/10 dark:bg-white/10",
                className,
            )}
            {...props}
        >
            <div
                className="h-full w-full translate-x-[-100%] rounded-[var(--radius-pill)] bg-secondary transition-transform duration-300 ease-out"
                style={{
                    transform: `translateX(${Math.min(100, Math.max(0, value)) - 100}%)`,
                }}
            />
        </div>
    ),
);
Progress.displayName = "Progress";
