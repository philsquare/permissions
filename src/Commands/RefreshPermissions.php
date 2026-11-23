<?php

namespace Philsquare\Permissions\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Philsquare\Permissions\Services\NameBuilder;
use ReflectionClass;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'permissions:refresh')]
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
    protected $description = 'Sync roles and permissions from your policies to the database';

    public function handle(PermissionRegistrar $registrar): int
    {
        $grants = $this->declaredGrants();

        // An empty scan means something is wrong with discovery, not that every
        // grant should go: syncing it would lock everyone out of the app.
        if ($grants->isEmpty()) {
            $this->components->warn('No policies declaring rolePermissions() were found. Nothing was changed.');

            return self::SUCCESS;
        }

        $roleClass = $registrar->getRoleClass();
        $permissionClass = $registrar->getPermissionClass();
        $guard = Guard::getDefaultName($permissionClass);

        try {
            // One transaction, so requests keep seeing the old grants until the
            // new ones are complete and a failure leaves the old ones in place.
            (new $roleClass)->getConnection()->transaction(
                fn () => $this->sync($grants, $roleClass, $permissionClass, $guard)
            );
        } finally {
            // Also on failure: the Spatie cache may have been rebuilt from rows
            // that have since been rolled back.
            $registrar->forgetCachedPermissions();
        }

        $this->components->info(sprintf(
            'Synced %d permissions across %d roles.',
            $grants->flatten()->unique()->count(),
            $grants->count()
        ));

        return self::SUCCESS;
    }

    /**
     * Make the role-permission pivot match the policies exactly. Any role a
     * policy doesn't declare ends up with no permissions.
     *
     * @param  Collection<string, Collection<int, string>>  $grants
     * @param  class-string<Model&RoleContract>  $roleClass
     * @param  class-string<Model&PermissionContract>  $permissionClass
     */
    protected function sync(Collection $grants, string $roleClass, string $permissionClass, string $guard): void
    {
        $permissionIds = $this->permissionIds($grants->flatten()->unique(), $permissionClass, $guard);

        $existingRoles = $roleClass::query()->where('guard_name', $guard)->pluck('name');

        $grants->keys()
            ->diff($existingRoles)
            ->each(fn (string $role) => $roleClass::create(['name' => $role, 'guard_name' => $guard]));

        foreach ($roleClass::query()->get() as $role) {
            $names = $role->getAttribute('guard_name') === $guard
                ? ($grants[$role->getAttribute('name')] ?? [])
                : [];

            $role->permissions()->sync($permissionIds->only($names)->values()->all());
        }
    }

    /**
     * @param  Collection<int, string>  $names
     * @param  class-string<Model&PermissionContract>  $permissionClass
     * @return Collection<string, int|string> Permission keys, by permission name.
     */
    protected function permissionIds(Collection $names, string $permissionClass, string $guard): Collection
    {
        $permissions = $permissionClass::query()
            ->where('guard_name', $guard)
            ->pluck((new $permissionClass)->getKeyName(), 'name');

        $names->diff($permissions->keys())->each(function (string $name) use ($permissions, $permissionClass, $guard): void {
            // Through the query builder: Spatie's static create() re-reads its whole
            // cached list for each new row, and throws if that cache is stale.
            $permissions->put($name, $permissionClass::query()->create(['name' => $name, 'guard_name' => $guard])->getKey());
        });

        return $permissions;
    }

    /**
     * @return Collection<string, Collection<int, string>> Permission names, by role.
     */
    protected function declaredGrants(): Collection
    {
        $nameBuilder = new NameBuilder;
        $grants = [];

        foreach ($this->policies() as $policy) {
            foreach (app($policy)->rolePermissions() as $role => $methods) {
                $grants[$role] ??= [];

                foreach ($methods as $method) {
                    $grants[$role][] = $nameBuilder->build($policy, $method);
                }
            }
        }

        return collect($grants)->map(fn (array $names) => collect($names)->unique()->values());
    }

    /**
     * Policies in the app's Policies folder, at any depth, plus any registered
     * with Gate::policy(), which can live anywhere.
     *
     * @return array<int, string>
     */
    protected function policies(): array
    {
        return collect($this->policiesInAppFolder())
            ->merge(array_values(Gate::policies()))
            ->unique()
            ->filter(fn (string $class) => class_exists($class))
            ->filter(fn (string $class) => method_exists($class, 'rolePermissions'))
            ->filter(fn (string $class) => (new ReflectionClass($class))->isInstantiable())
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function policiesInAppFolder(): array
    {
        $path = app_path('Policies');

        if (! File::isDirectory($path)) {
            return [];
        }

        $namespace = app()->getNamespace().'Policies\\';

        return collect(File::allFiles($path))
            ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
            ->map(fn (SplFileInfo $file) => $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname()
            ))
            ->values()
            ->all();
    }
}
