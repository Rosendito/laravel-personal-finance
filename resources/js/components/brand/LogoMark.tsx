import { cn } from "@/lib/utils";

type LogoMarkProps = {
    className?: string;
};

export const LogoMark = ({ className }: LogoMarkProps) => (
    <div
        className={cn(
            "inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary text-base font-semibold text-primary-foreground shadow-sm",
            className,
        )}
    >
        PF
    </div>
);
