<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

/** Filament's ->authorize('deleteAny', $owner) prepends the model class. */
class ItemPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['quoter' => $this->permissions()->all()];
    }

    public function deleteAny(User $user, Post $owner): bool
    {
        return $owner->title !== 'locked';
    }
}
