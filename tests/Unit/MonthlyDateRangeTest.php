<?php

declare(strict_types=1);

use App\Support\Dates\MonthlyDateRange;
use Carbon\CarbonImmutable;

describe(MonthlyDateRange::class, function (): void {
    it('returns start and end of month for a given date', function (string $date, string $expectedStart, string $expectedEnd): void {
        [$start, $end] = MonthlyDateRange::forDate(CarbonImmutable::parse($date));

        expect($start->toDateString())->toBe($expectedStart);
        expect($end->toDateString())->toBe($expectedEnd);
    })->with([
        'regular month' => ['2026-01-17', '2026-01-01', '2026-01-31'],
        'leap year february' => ['2024-02-10', '2024-02-01', '2024-02-29'],
        'thirty-day month' => ['2026-04-30', '2026-04-01', '2026-04-30'],
    ]);
});
