<?php

namespace App;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** The usual super-admin pattern, booted like an app provider: after the package's. */
class SuperAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);
    }
}
