<?php

namespace App\Models;

use Spatie\Permission\Models\Role;

/** Records creation, proving the sync goes through the configured model. */
class TrackedRole extends Role
{
    public static int $created = 0;

    protected $table = 'roles';

    protected static function booted(): void
    {
        static::created(fn () => static::$created++);
    }
}
