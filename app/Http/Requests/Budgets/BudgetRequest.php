<?php

declare(strict_types=1);

namespace App\Http\Requests\Budgets;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class BudgetRequest extends FormRequest
{
    final protected function actingUser(): User
    {
        $user = $this->user();

        if ($user instanceof User) {
            return $user;
        }

        return User::query()->firstOrFail();
    }
}
