<?php

namespace Philsquare\Permissions\Traits;

use Philsquare\Permissions\Services\Permissions;

trait InteractWithPermissions
{
    protected function permissions(): Permissions
    {
        return new Permissions(static::class);
    }
}
