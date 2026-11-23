<?php

namespace App\Policies;

use App\Models\User;
use Philsquare\Permissions\Contracts\ProvidesRolePermissions;
use Philsquare\Permissions\Traits\InteractWithPermissions;

/** Declares its grants but opts out of the before() gate by not extending BasePolicy. */
class TimeCardPolicy implements ProvidesRolePermissions
{
    use InteractWithPermissions;

    public function rolePermissions(): array
    {
        return ['admin' => $this->permissions()->all()];
    }

    public function view(User $user): bool
    {
        return true;
    }
}
