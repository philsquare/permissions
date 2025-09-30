<?php

namespace Philsquare\Permissions\Commands;

use Philsquare\Permissions\Contracts\ProvidesRolePermissions;
use Philsquare\Permissions\Services\NameBuilder;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RefreshPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates permissions and reassigns all roles.';

    /**
     * Registered list of roles for applying permissions.
     *
     * @var string[]
     */
    protected array $rolesWithPermissions = [];

    protected Collection $roles;

    protected Collection $permissions;

    public function handle(): int
    {
        $this->permissions = Permission::query()
            ->get();

        $this->buildRolePermissions();

        $this->setRoles();

        $this->clearPermissionsAssignments();

        $this->applyPermissionsToRoles();

        app()
            ->make(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return 0;
    }

    protected function buildRolePermissions()
    {
        $this->getPolicies()
            ->map(
                fn ($policy) => $this->addToRolePermission($policy)
            );
    }

    protected function addToRolePermission(string $policy)
    {
        collect($this->getPolicy($policy)->rolePermissions())
            ->map(
                fn ($methods, $role) => collect($methods)
                    ->map(
                        fn ($method) => NameBuilder::make()->build($policy, $method)
                    )
                    ->each(
                        fn ($permission) => $this->rolesWithPermissions[$role][] = $permission
                    )
            );
    }

    protected function getPolicy(string $class)
    {
        return new $class;
    }

    protected function setRoles()
    {
        $roles = Role::all();

        collect($this->rolesWithPermissions)
            ->keys()
            ->filter(fn ($role) => $roles->doesntContain(
                'name', $role
            ))
            ->each(fn ($role) => Role::create([
                'name' => $role,
            ]));

        $this->roles = Role::all();
    }

    /**
     * Remove all Nova permission assignments.
     */
    protected function clearPermissionsAssignments(): void
    {
        DB::table('role_has_permissions')
            ->truncate();
    }

    /**
     * Apply permissions to all roles appropriately.
     */
    protected function applyPermissionsToRoles()
    {
        collect($this->rolesWithPermissions)
            ->each(fn ($permissions, $role) => $this->applyPermissions($permissions, $role));
    }

    /**
     * Apply permissions to supplied role.
     */
    protected function applyPermissions(array $permissions, string $role): void
    {
        $role = $this->getRoleModel($role);

        $permissions = collect($permissions)
            ->map(fn ($permission) => $this->findOrCreatePermission(
                $permission
            ));

        $role
            ->permissions()
            ->attach($permissions->pluck('id'));
    }

    /**
     * Find or create permission and update permission list if needed.
     */
    protected function findOrCreatePermission(string $permission): Model
    {
        if ($this->permissionsExists($permission)) {
            return $this->permissions->where('name', $permission)->first();
        }

        $permission = $this->createPermission($permission);

        $this->permissions->push($permission);

        return $permission;
    }

    /**
     * Determine if permission exists in current list.
     *
     * @return bool
     */
    protected function permissionsExists(string $permission)
    {
        return $this
            ->permissions
            ->contains('name', $permission);
    }

    /**
     * Create a new permission in storage.
     */
    protected function createPermission(string $permission): Model
    {
        return Permission::create([
            'guard_name' => 'web',
            'name' => $permission,
        ]);
    }

    protected function getPolicies(): Collection
    {
        $policyPath = app_path('Policies');

        if (! File::exists($policyPath)) {
            return collect();
        }

        return collect(File::files($policyPath))
            ->map(fn ($file) => $this->getClassName($file))
            ->filter(fn ($class) => class_exists($class))
            ->filter(fn ($class) => method_exists($class, 'rolePermissions'));
    }

    protected function getClassName($file): string
    {
        return 'App\\Policies\\'.$file->getBasename('.php');
    }

    protected function implementsInterface(string $class): bool
    {
        return (new ReflectionClass($class))
            ->implementsInterface(ProvidesRolePermissions::class);
    }

    /**
     * @return Closure|null
     */
    protected function getRoleModel(string $role): Role
    {
        return $this
            ->roles
            ->where('name', $role)
            ->first();
    }
}
