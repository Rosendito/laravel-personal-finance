<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;

trait ResolvesActingUser
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
