<?php

namespace Philsquare\Permissions\Filament;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Philsquare\Permissions\BasePolicy;
use Philsquare\Permissions\Services\PermissionChecker;

use function Illuminate\Support\enum_value;

/**
 * Closes a gap between Filament and BasePolicy.
 *
 * When a policy doesn't define the method for an ability, Filament doesn't call
 * the policy at all, so BasePolicy::before() never runs. It consults only the
 * Gate::before callbacks, and allows the action unless one of them returns false.
 * A role with no access would otherwise be able to do anything the policy forgot
 * to mention, such as the bulk delete every generated resource includes.
 *
 * Registered as a Gate::before callback, this returns false for those abilities
 * unless the user holds the permission that stands in for them.
 */
class DenyUndefinedAbilities
{
    /**
     * Abilities Filament asks for, and the ability whose permission stands in when
     * the policy leaves them out. A stand-in the policy also leaves out passes to
     * its own stand-in. Null means nothing stands in, so the ability is denied until
     * the policy defines it.
     *
     * @var array<string, string|null>
     */
    protected const ABILITIES = [
        'viewAny' => null,
        'view' => null,
        'create' => null,
        'update' => null,
        'delete' => null,
        'restore' => 'delete',
        'forceDelete' => 'delete',
        'deleteAny' => 'delete',
        'restoreAny' => 'restore',
        'forceDeleteAny' => 'forceDelete',
        'replicate' => 'create',
        'reorder' => 'update',
    ];

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __invoke(Authenticatable $user, mixed $ability, array $arguments): ?bool
    {
        $ability = enum_value($ability);

        if (! array_key_exists($ability, self::ABILITIES) || Gate::has($ability)) {
            return null;
        }

        $policy = Gate::getPolicyFor($arguments[0] ?? null);

        if (! $policy instanceof BasePolicy || method_exists($policy, $ability)) {
            return null;
        }

        $standIn = $this->standInFor($policy, $ability);

        return $standIn !== null && (new PermissionChecker)->allows($user, $policy::class, $standIn)
            ? null
            : false;
    }

    /**
     * A role can only hold the permission for an ability the policy defines, so
     * walk the table until reaching one.
     */
    protected function standInFor(BasePolicy $policy, string $ability): ?string
    {
        do {
            $ability = self::ABILITIES[$ability] ?? null;
        } while ($ability !== null && ! method_exists($policy, $ability));

        return $ability;
    }
}
