<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\LedgerAccount;
use App\Models\User;
use App\Observers\LedgerAccountObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootObservers();
    }

    private function bootModelsDefaults(): void
    {
        Model::unguard();
    }

    private function bootObservers(): void
    {
        User::observe(UserObserver::class);
        LedgerAccount::observe(LedgerAccountObserver::class);
    }
}
