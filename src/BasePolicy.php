<?php
namespace Philsquare\Permissions;

use App\Models\User;
use Illuminate\Support\Str;
use Philsquare\Permissions\Contracts\ProvidesRolePermissions;
use Philsquare\Permissions\Traits\InteractWithPermissions;
use ReflectionClass;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

abstract class BasePolicy implements ProvidesRolePermissions
{
    use InteractWithPermissions;

    public function before(User $user, string $ability, $arguments): ?bool
    {
        $prefix = Str::kebab(
            new ReflectionClass($arguments)->getShortName()
        );

        $permission = $prefix.':'.Str::kebab($ability);

        foreach ($user->roles as $role) {
            try {
                if ($role->hasPermissionTo($permission)) {
                    return null;
                }
            } catch (PermissionDoesNotExist) {
                // The permission was never registered, so no role can hold it.
                // Deny rather than surfacing the exception to the caller.
                return false;
            }
        }

        return false;
    }
}
