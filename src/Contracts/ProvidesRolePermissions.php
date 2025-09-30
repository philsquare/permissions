<?php

namespace Philsquare\Permissions\Contracts;

interface ProvidesRolePermissions
{
    public function rolePermissions(): array;
}
