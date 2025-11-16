type CurrencyValue = string | number;

const currencyFormatter = (
    currency: string,
    options?: Intl.NumberFormatOptions,
): Intl.NumberFormat => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
        ...options,
    });
};

export const formatCurrency = (
    value: CurrencyValue,
    currency = "USD",
    options?: Intl.NumberFormatOptions,
): string => {
    const numericValue = typeof value === "string" ? Number(value) : value;

    if (Number.isNaN(numericValue)) {
        return "--";
    }

    return currencyFormatter(currency, options).format(numericValue);
};

export const formatSignedCurrency = (
    value: CurrencyValue,
    currency = "USD",
    options?: Intl.NumberFormatOptions,
): string => {
    const formatted = formatCurrency(value, currency, options);

    if (Number(value) > 0) {
        return `+${formatted}`;
    }

    return formatted;
};
