<?php

namespace App\Policies;

use Philsquare\Permissions\BasePolicy;

/** Blows up once the sync has started writing, to prove the writes roll back. */
class BrokenPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['admin' => [str_repeat('x', 300)]];
    }
}
