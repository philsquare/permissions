<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

/** One table, three models, each subclass with its own policy and prefix. */
class ServicePolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['technician' => $this->permissions()->all()];
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }
}
