<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Philsquare\Permissions\BasePolicy;

/** An app-level gate in front of the role check, as UPGRADE.md documents. */
class ModulePolicy extends BasePolicy
{
    public function before(Authenticatable $user, string $ability, mixed $subject = null): ?bool
    {
        if (is_object($subject) && ! $subject->enabled) {
            return false;
        }

        return parent::before($user, $ability, $subject);
    }

    public function rolePermissions(): array
    {
        return ['operator' => $this->permissions()->all()];
    }

    public function view(User $user): bool
    {
        return true;
    }
}
