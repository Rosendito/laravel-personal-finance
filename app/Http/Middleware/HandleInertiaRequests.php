<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name', 'Personal Finance'),
            'initialTheme' => $request->cookie('theme', 'system'),
            'viewer' => static function () use ($request): ?array {
                $user = $request->user() ?? User::query()->select(['name', 'email'])->first();

                return $user?->only(['name', 'email']);
            },
            'primaryNavigation' => [
                [
                    'id' => 'dashboard',
                    'label' => 'Overview',
                    'description' => 'Resumen general de tus finanzas.',
                    'href' => route('dashboard', absolute: false),
                    'icon' => 'layout-dashboard',
                ],
                [
                    'id' => 'accounts',
                    'label' => 'Accounts',
                    'description' => 'Saldos y actividad por cuenta.',
                    'href' => route('accounts.index', absolute: false),
                    'icon' => 'wallet',
                ],
                [
                    'id' => 'transactions',
                    'label' => 'Transactions',
                    'description' => 'Movimientos recientes y filtros.',
                    'href' => route('transactions.index', absolute: false),
                    'icon' => 'arrows-left-right',
                ],
                [
                    'id' => 'budgets',
                    'label' => 'Budgets',
                    'description' => 'Controla objetivos y asignaciones.',
                    'href' => route('budgets.index', absolute: false),
                    'icon' => 'piggy-bank',
                ],
            ],
        ];
    }
}
