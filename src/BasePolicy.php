<?php
namespace Philsquare\Permissions;

use App\Models\User;
use Illuminate\Support\Str;
use Philsquare\Permissions\Contracts\ProvidesRolePermissions;
use Philsquare\Permissions\Traits\InteractWithPermissions;
use ReflectionClass;
use Spatie\Permission\Models\Role;

abstract class BasePolicy implements ProvidesRolePermissions
{
    use InteractWithPermissions;

    public function before(User $user, string $ability, $arguments): ?bool
    {
        $prefix = Str::kebab(
            new ReflectionClass($arguments)->getShortName()
        );

        $roles = $user->roles;
        $hasPermissions = false;

        foreach ($roles as $role) {
            if ($role->hasPermissionTo("$prefix:$ability")) {
                $hasPermissions = true;
            }
        }

        if ($hasPermissions) {
            return null;
        }

        return false;
    }
}
