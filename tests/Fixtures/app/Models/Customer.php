<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * A second authenticatable, standing in for a dedicated Filament panel user.
 * It deliberately does not extend App\Models\User.
 */
class Customer extends Authenticatable
{
    use HasRoles;

    protected $guarded = [];

    protected $table = 'customers';
}
