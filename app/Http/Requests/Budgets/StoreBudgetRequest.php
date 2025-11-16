<?php

declare(strict_types=1);

namespace App\Http\Requests\Budgets;

use App\Concerns\ResolvesActingUser;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBudgetRequest extends FormRequest
{
    use ResolvesActingUser;

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

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('budgets', 'name')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query
                        ->where('user_id', $actingUser->id)
                ),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
