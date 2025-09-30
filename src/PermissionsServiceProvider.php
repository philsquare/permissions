<?php
namespace Philsquare\Permissions;

use Philsquare\Permissions\Commands\RefreshPermissions;
use Philsquare\Permissions\Commands\PolicyMakeCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PermissionsServiceProvider extends PackageServiceProvider
{

    public function configurePackage(\Spatie\LaravelPackageTools\Package $package): void
    {
        $package->name('philsquare-permissions')->hasCommands([
            PolicyMakeCommand::class,
            RefreshPermissions::class,
        ]);
    }
}