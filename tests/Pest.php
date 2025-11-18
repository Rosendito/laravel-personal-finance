<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

pest()->presets()->custom('strictWithLaravelExceptions', function (array $namespaces): array {
    $expectations = [];

    foreach ($namespaces as $namespace) {
        $expectations[] = expect($namespace)
            ->classes()
            ->not
            ->toBeAbstract();

        $expectations[] = expect($namespace)->toUseStrictTypes();

        $expectations[] = expect($namespace)->toUseStrictEquality();

        $expectations[] = expect($namespace)
            ->classes()
            ->toBeFinal();
    }

    $expectations[] = expect([
        'sleep',
        'usleep',
    ])->not->toBeUsed();

    return $expectations;
});

function something(): void
{
    // ..
}
