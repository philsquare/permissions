<?php

namespace Philsquare\Permissions\Tests\Fixtures;

use App\Models\User;
use Philsquare\Permissions\BasePolicy;

/** Defines restore() but not forceDelete(), so only restore is its own permission. */
class SoftDeletingPostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'editor' => $this->permissions()->crud(),
            'restorer' => $this->permissions()->only(['viewAny', 'restore']),
        ];
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function delete(User $user): bool
    {
        return true;
    }

    public function restore(User $user): bool
    {
        return true;
    }
}
