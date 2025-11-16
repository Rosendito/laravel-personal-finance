<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @deprecated
 */
final class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->resolveUser($request);
        $search = $request->string('search')->trim()->value();

        $budgets = Budget::query()
            ->whereBelongsTo($user)
            ->when(
                $search,
                static fn ($query, string $term) => $query->where('name', 'like', sprintf('%%%s%%', $term))
            )
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $budgets->count(),
            'active' => $budgets->where('is_active', true)->count(),
            'inactive' => $budgets->where('is_active', false)->count(),
        ];

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets
                ->map(static fn (Budget $budget): array => [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'is_active' => $budget->is_active,
                    'created_at' => $budget->created_at?->toIso8601String(),
                    'updated_at' => $budget->updated_at?->toIso8601String(),
                ])
                ->values(),
            'filters' => [
                'search' => $search,
            ],
            'stats' => $stats,
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $user = $this->resolveUser($request);
        $payload = $request->validated();
        $payload['user_id'] = $user->id;

        $budget = Budget::query()->create($payload);

        return to_route('budgets.index');
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $user = $this->resolveUser($request);
        $this->ensureBudgetOwner($budget, $user);

        $budget->update($request->validated());

        return to_route('budgets.index');
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        $user = $this->resolveUser($request);
        $this->ensureBudgetOwner($budget, $user);
        $budget->delete();

        return to_route('budgets.index');
    }

    private function resolveUser(Request $request): User
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $user;
        }

        return User::query()->firstOrFail();
    }

    private function ensureBudgetOwner(Budget $budget, User $user): void
    {
        if ($budget->user_id !== $user->id) {
            abort(404);
        }
    }
}
