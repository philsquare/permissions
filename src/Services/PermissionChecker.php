<?php

namespace Philsquare\Permissions\Services;

use Illuminate\Contracts\Auth\Authenticatable;

class PermissionChecker
{
    public function __construct(protected NameBuilder $names = new NameBuilder) {}

    /**
     * Whether any of the user's roles holds the permission for a policy's ability.
     *
     * Asks each role rather than the user because a role checks against its own
     * guard. A Filament panel's guard becomes the default guard mid-request, and
     * the user-level check would look for permissions on that guard instead.
     */
    public function allows(Authenticatable $user, string $policy, string $ability): bool
    {
        $permission = $this->names->build($policy, $ability);

        foreach ($user->roles ?? [] as $role) {
            if ($role->checkPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }
}
