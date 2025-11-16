import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const badgeVariants = cva(
    "inline-flex items-center rounded-[var(--radius-pill)] px-3 py-1 text-xs font-medium",
    {
        variants: {
            variant: {
                default: "bg-ink/10 text-ink dark:text-ink",
                success: "bg-success/15 text-success",
                warning: "bg-warning/20 text-warning",
                danger: "bg-danger/15 text-danger",
                info: "bg-secondary/15 text-secondary",
            },
        },
        defaultVariants: {
            variant: "default",
        },
    },
);

export type BadgeProps = React.HTMLAttributes<HTMLDivElement> &
    VariantProps<typeof badgeVariants>;

export const Badge = ({ className, variant, ...props }: BadgeProps) => (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
);
