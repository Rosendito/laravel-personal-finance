<?php

declare(strict_types=1);

namespace App\Providers;

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
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\LedgerAccount::observe(\App\Observers\LedgerAccountObserver::class);
    }
}
