<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\InitializeUserSpace;
use App\Models\User;

final class UserObserver
{
    public function __construct(
        private readonly InitializeUserSpace $initializeUserSpace,
    ) {}

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->initializeUserSpace->execute($user);
    }
}
