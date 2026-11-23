<?php

namespace Philsquare\Permissions\Tests\Fixtures;

use App\Models\User;
use Philsquare\Permissions\BasePolicy;

/** Defines deleteAny() itself, with the typed signature apps commonly use. */
class BulkPostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'editor' => $this->permissions()->crud(),
            'bulk-editor' => $this->permissions()->crud(['deleteAny']),
        ];
    }

    public function delete(User $user): bool
    {
        return true;
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }
}
