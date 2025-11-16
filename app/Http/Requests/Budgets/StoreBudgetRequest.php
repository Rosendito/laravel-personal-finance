<?php

declare(strict_types=1);

namespace App\Http\Requests\Budgets;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

final class StoreBudgetRequest extends BudgetRequest
{
    public function authorize(): bool
    {
        $this->actingUser();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $actingUser = $this->actingUser();
        $period = $this->string('period')->value();

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('budgets', 'name')->where(
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
