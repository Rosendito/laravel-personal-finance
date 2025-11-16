export const formatPeriod = (period: string): string => {
    const [year, month] = period.split("-");

    if (!year || !month) {
        return period;
    }

    const date = new Date(Number(year), Number(month) - 1, 1);

    return new Intl.DateTimeFormat("es-ES", {
        month: "long",
        year: "numeric",
    }).format(date);
};

export const formatShortDate = (value?: string | null): string => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "—";
    }

    return new Intl.DateTimeFormat("es-ES", {
        day: "numeric",
        month: "short",
    }).format(date);
};

