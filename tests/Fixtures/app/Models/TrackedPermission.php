<?php

namespace App\Models;

use Spatie\Permission\Models\Permission;

/** Records creation, proving the sync goes through the configured model. */
class TrackedPermission extends Permission
{
    public static int $created = 0;

    protected $table = 'permissions';

    protected static function booted(): void
    {
        static::created(fn () => static::$created++);
    }
}
