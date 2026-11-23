<?php

namespace App\Policies;

use Philsquare\Permissions\BasePolicy;

/** A shared parent living alongside real policies in app/Policies. */
abstract class SharedPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['admin' => $this->permissions()->all()];
    }
}
