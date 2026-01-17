<?php

declare(strict_types=1);

namespace App\Support\Dates;

use Carbon\CarbonImmutable;

final class MonthlyDateRange
{
    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function forDate(CarbonImmutable $date): array
    {
        $monthStart = $date->startOfMonth();
        $monthEnd = $date->endOfMonth();

        return [$monthStart, $monthEnd];
    }
}
