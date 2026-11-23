<?php

namespace App\Policies;

use Philsquare\Permissions\BasePolicy;

class NoticePolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['guest' => []];
    }

    public function view(): bool
    {
        return true;
    }
}
