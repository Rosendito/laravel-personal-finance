<?php

declare(strict_types=1);

namespace App\Http\Requests\Budgets;

use App\Concerns\ResolvesActingUser;
use App\Models\Budget;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBudgetRequest extends FormRequest
{
    use ResolvesActingUser;

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
                    ),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
