<?php

declare(strict_types=1);

namespace App\Http\Requests\Budgets;

use App\Models\Budget;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

final class UpdateBudgetRequest extends BudgetRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('budget');

        if (! $budget instanceof Budget) {
            return false;
        }

        return $budget->user_id === $this->actingUser()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Budget $budget */
        $budget = $this->route('budget');
        $actingUser = $this->actingUser();
        $period = $this->string('period')->value() ?? $budget->period;

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('budgets', 'name')
                    ->ignore($budget->id)
                    ->where(
                        static fn (QueryBuilder $query): QueryBuilder => $query
                            ->where('user_id', $actingUser->id)
                            ->where('period', $period)
                    ),
            ],
            'period' => ['required', 'date_format:Y-m'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
