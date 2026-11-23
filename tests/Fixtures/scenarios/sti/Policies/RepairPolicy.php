<?php

namespace App\Policies;

use App\Models\Repair;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class RepairPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['technician' => $this->permissions()->all()];
    }

    public function view(User $user, Repair $repair): bool
    {
        return true;
    }
}
