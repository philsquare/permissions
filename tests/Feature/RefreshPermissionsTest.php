<?php

use App\Models\Post;
use App\Models\TrackedPermission;
use App\Models\TrackedRole;
use App\Policies\ArticlePolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Philsquare\Permissions\Tests\Fixtures\ExternalPostPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function grantsFor(string $role): array
{
    return Role::findByName($role)->permissions()->pluck('name')->sort()->values()->all();
}

it('creates the roles and permissions a policy declares', function (): void {
    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'editor', 'viewer'])
        ->and(grantsFor('viewer'))->toBe(['post:view', 'post:view-any'])
        ->and(grantsFor('editor'))->toBe(['post:create', 'post:delete', 'post:update', 'post:view', 'post:view-any']);
});

it('does not emit Gate hooks or helpers as permissions', function (): void {
    $this->artisan('permissions:refresh');

    expect(Permission::pluck('name')->all())
        ->not->toContain('post:before', 'post:after', 'post:role-permissions', 'post:permissions');
});

it('is idempotent', function (): void {
    $this->artisan('permissions:refresh');
    $first = DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get();

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get())->toEqual($first)
        ->and(Permission::count())->toBe(Permission::distinct()->count('name'));
});

it('revokes grants that a policy no longer declares', function (): void {
    $this->artisan('permissions:refresh');

    Role::findByName('viewer')->givePermissionTo('post:delete');

    $this->artisan('permissions:refresh');

    expect(grantsFor('viewer'))->not->toContain('post:delete');
});

it('revokes the grants of a role that no policy declares any more', function (): void {
    $this->artisan('permissions:refresh');

    Role::create(['name' => 'retired'])->givePermissionTo('post:view');

    $this->artisan('permissions:refresh');

    expect(grantsFor('retired'))->toBe([]);
});

it('flushes the Spatie permission cache', function (): void {
    $this->artisan('permissions:refresh');

    $registrar = app(PermissionRegistrar::class);
    $registrar->getPermissions();

    $this->artisan('permissions:refresh');

    expect(cache()->store(config('permission.cache.store') === 'default' ? null : config('permission.cache.store'))
        ->has($registrar->cacheKey))->toBeFalse();
});

it('accepts crud() additions that repeat a CRUD method', function (): void {
    $this->usePolicyScenario('duplicates');

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('editor'))->toContain('duplicate-grant:view', 'duplicate-grant:publish');
});

it('keeps existing grants when a sync fails part way through', function (): void {
    $this->artisan('permissions:refresh');
    $before = grantsFor('viewer');

    Schema::table('permissions', function (Blueprint $table): void {
        // sqlite ignores varchar lengths, so enforce the limit MySQL would.
        DB::unprepared("CREATE TRIGGER limit_permission_name BEFORE INSERT ON permissions
            WHEN length(NEW.name) > 125 BEGIN SELECT RAISE(ABORT, 'name too long'); END");
    });

    $this->usePolicyScenario('broken');

    try {
        Artisan::call('permissions:refresh');
    } catch (Throwable) {
        // The failure itself is expected; what matters is the state afterwards.
    }

    expect(grantsFor('viewer'))->toBe($before);
});

it('ignores abstract classes in the policies folder', function (): void {
    $this->usePolicyScenario('abstract');

    expect(class_exists(ArticlePolicy::class))->toBeTrue();

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('admin'))->toBe(['article:view']);
});

it('resolves policies through the container and skips magic and static methods', function (): void {
    $this->usePolicyScenario('constructor');

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('admin'))->toBe(['widget:view']);
});

it('discovers policies in nested folders', function (): void {
    $this->usePolicyScenario('nested');

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('accountant'))->toBe(['invoice:view']);
});

it('keeps the word Policy when it is part of the model name', function (): void {
    $this->usePolicyScenario('policy-in-name');

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('agent'))->toBe(['insurance-policy:view']);
});

it('creates roles and permissions on the default guard', function (): void {
    config([
        'auth.defaults.guard' => 'staff',
        'auth.guards.staff' => ['driver' => 'session', 'provider' => 'users'],
    ]);

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(Role::pluck('guard_name')->unique()->all())->toBe(['staff'])
        ->and(Permission::pluck('guard_name')->unique()->all())->toBe(['staff']);
});

it('reads the pivot table name from Spatie config', function (): void {
    Schema::rename('role_has_permissions', 'custom_role_permissions');
    config(['permission.table_names.role_has_permissions' => 'custom_role_permissions']);

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(DB::table('custom_role_permissions')->count())->toBeGreaterThan(0);
});

it('uses the Role and Permission models configured for Spatie', function (): void {
    config([
        'permission.models.role' => TrackedRole::class,
        'permission.models.permission' => TrackedPermission::class,
    ]);
    app()->forgetInstance(PermissionRegistrar::class);

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(TrackedRole::$created)->toBeGreaterThan(0)
        ->and(TrackedPermission::$created)->toBeGreaterThan(0);
});

it('reports what it did', function (): void {
    $this->artisan('permissions:refresh')
        ->expectsOutputToContain('role')
        ->assertSuccessful();
});

it('creates a role declared with no permissions', function (): void {
    $this->usePolicyScenario('empty-role');

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(Role::where('name', 'guest')->exists())->toBeTrue()
        ->and(grantsFor('guest'))->toBe([]);
});

it('leaves grants alone when no policies are found', function (): void {
    $this->artisan('permissions:refresh');
    $before = grantsFor('viewer');

    $this->app->useAppPath(__DIR__.'/../Fixtures/no-policies');

    $this->artisan('permissions:refresh')
        ->expectsOutputToContain('Nothing was changed')
        ->assertSuccessful();

    expect(grantsFor('viewer'))->toBe($before);
});

it('syncs policies registered with Gate outside the policies folder', function (): void {
    Gate::policy(Post::class, ExternalPostPolicy::class);

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('external'))->toBe(['external-post:view']);
});

it('is not thrown off by a stale permission cache', function (): void {
    $this->artisan('permissions:refresh');

    // The cache still lists these rows, as it does after migrate:fresh.
    app(PermissionRegistrar::class)->getPermissions();
    DB::table('permissions')->delete();

    $this->artisan('permissions:refresh')->assertSuccessful();

    expect(grantsFor('viewer'))->toBe(['post:view', 'post:view-any']);
});

it('can run twice in one process', function (): void {
    Artisan::call('permissions:refresh');

    expect(Artisan::call('permissions:refresh'))->toBe(0);
});
