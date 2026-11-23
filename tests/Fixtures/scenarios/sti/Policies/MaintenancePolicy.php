<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class MaintenancePolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['planner' => $this->permissions()->all()];
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        return true;
    }
}
