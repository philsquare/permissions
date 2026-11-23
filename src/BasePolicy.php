<?php

namespace Philsquare\Permissions;

use Illuminate\Contracts\Auth\Authenticatable;
use Philsquare\Permissions\Contracts\ProvidesRolePermissions;
use Philsquare\Permissions\Services\PermissionChecker;
use Philsquare\Permissions\Traits\InteractWithPermissions;

abstract class BasePolicy implements ProvidesRolePermissions
{
    use InteractWithPermissions;

    /**
     * Gate calls this with the model or class being authorized as $subject. The
     * permission comes from the policy, so that is only here for overrides.
     */
    public function before(Authenticatable $user, string $ability, mixed $subject = null): ?bool
    {
        return (new PermissionChecker)->allows($user, static::class, $ability) ? null : false;
    }
}
