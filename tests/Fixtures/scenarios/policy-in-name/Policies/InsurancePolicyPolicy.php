<?php

namespace App\Policies;

use App\Models\InsurancePolicy;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class InsurancePolicyPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['agent' => $this->permissions()->all()];
    }

    public function view(User $user, InsurancePolicy $insurancePolicy): bool
    {
        return true;
    }
}
