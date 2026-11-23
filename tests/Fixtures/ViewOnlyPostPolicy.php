<?php

namespace Philsquare\Permissions\Tests\Fixtures;

use App\Models\User;
use Philsquare\Permissions\BasePolicy;

/** Defines nothing but viewAny(), so no other ability has a permission to fall back on. */
class ViewOnlyPostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['reader' => $this->permissions()->all()];
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
