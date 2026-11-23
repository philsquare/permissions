<?php

namespace App\Policies;

use Illuminate\Contracts\Config\Repository;
use Philsquare\Permissions\BasePolicy;

/** Gate resolves policies through the container, so injection is legitimate. */
class WidgetPolicy extends BasePolicy
{
    public function __construct(protected Repository $config) {}

    public function rolePermissions(): array
    {
        return ['admin' => $this->permissions()->all()];
    }

    public static function label(): string
    {
        return 'Widget';
    }

    public function view(): bool
    {
        return true;
    }
}
