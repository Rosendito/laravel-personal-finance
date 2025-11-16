import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
    "inline-flex items-center justify-center rounded-[var(--radius-sm)] text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
    {
        variants: {
            variant: {
                primary:
                    "bg-primary text-primary-foreground shadow-sm hover:bg-primary-muted hover:shadow-md focus-visible:ring-secondary",
                secondary:
                    "bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/90 focus-visible:ring-secondary",
                accent: "bg-accent text-accent-foreground shadow-sm hover:bg-accent/90 focus-visible:ring-accent/60",
                outline:
                    "border border-ink/10 bg-surface text-ink hover:border-ink/30 hover:bg-surface-muted/60 focus-visible:ring-secondary",
                ghost: "text-ink-muted hover:text-ink hover:bg-ink/5 dark:hover:bg-surface-muted/60 focus-visible:ring-secondary/70",
                subtle: "bg-surface-muted text-ink shadow-sm hover:bg-surface-muted/70 focus-visible:ring-secondary/70",
            },
            size: {
                sm: "h-9 px-4",
                md: "h-11 px-5",
                lg: "h-12 px-6 text-base",
                icon: "h-10 w-10",
            },
        },
        defaultVariants: {
            variant: "primary",
            size: "md",
        },
    },
);

export type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof buttonVariants> & {
        asChild?: boolean;
    };

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, size, asChild = false, ...props }, ref) => {
        const Comp = asChild ? Slot : "button";

        return (
            <Comp
                className={cn(buttonVariants({ variant, size }), className)}
                ref={ref}
                {...props}
            />
        );
    },
);
Button.displayName = "Button";
