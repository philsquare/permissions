<?php

namespace Philsquare\Permissions\Tests\Fixtures;

use Philsquare\Permissions\BasePolicy;

/** Lives outside app/Policies, as a module's or plugin's policy would. */
class ExternalPostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['external' => $this->permissions()->all()];
    }

    public function view(): bool
    {
        return true;
    }
}
