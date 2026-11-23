<?php

namespace Philsquare\Permissions\Contracts;

interface ProvidesRolePermissions
{
    /**
     * Policy methods each role may call, by role name.
     *
     * @return array<string, iterable<int, string>>
     */
    public function rolePermissions(): array;
}
