<?php

namespace Philsquare\Permissions;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Philsquare\Permissions\Commands\PolicyMakeCommand;
use Philsquare\Permissions\Commands\RefreshPermissions;
use Philsquare\Permissions\Filament\DenyUndefinedAbilities;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PermissionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('philsquare-permissions')->hasCommands([
            PolicyMakeCommand::class,
            RefreshPermissions::class,
        ]);
    }

    public function packageBooted(): void
    {
        if (! class_exists(Filament::class)) {
            return;
        }

        // After every provider has booted, so this runs behind the app's own
        // Gate::before callbacks. A super-admin check must get to allow first.
        $this->app->booted(fn () => Gate::before((new DenyUndefinedAbilities)(...)));
    }
}
