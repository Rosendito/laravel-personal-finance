<?php

declare(strict_types=1);

namespace App\Helpers;

use function sprintf;

final class MoneyFormatter
{
    /**
     * Format an amount with currency code.
     *
     * @return string Formatted as "USD 1,234.56"
     */
    public static function format(int|float|string|null $amount, string $currencyCode): string
    {
        $formattedAmount = number_format((float) ($amount ?? 0), 2, '.', ',');

        return sprintf('%s %s', $currencyCode, $formattedAmount);
    }

    /**
     * Format a name with amount in parentheses.
     *
     * @return string Formatted as "Name (USD 1,234.56)"
     */
    public static function formatWithParentheses(string $name, int|float|string|null $amount, string $currencyCode): string
    {
        $formattedAmount = self::format($amount, $currencyCode);

        return sprintf('%s (%s)', $name, $formattedAmount);
    }
}
