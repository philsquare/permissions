<?php

namespace App\Policies;

use Philsquare\Permissions\BasePolicy;

class DuplicateGrantPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            // 'view' is already part of crud(), so a naive merge lists it twice.
            'editor' => $this->permissions()->crud(['view', 'publish']),
        ];
    }

    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public function publish(): bool
    {
        return true;
    }
}
