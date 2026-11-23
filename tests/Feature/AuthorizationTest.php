<?php

use App\Models\Billing\Invoice;
use App\Models\Customer;
use App\Models\FeaturedPost;
use App\Models\InsurancePolicy;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Module;
use App\Models\Post;
use App\Models\Repair;
use App\Models\Service;
use App\Models\TimeCard;
use App\Models\User;
use App\Policies\Billing\InvoicePolicy;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function userWithRole(string ...$roles): User
{
    $user = User::create(['name' => 'Test', 'email' => fake()->unique()->safeEmail()]);

    if ($roles !== []) {
        $user->assignRole($roles);
    }

    return $user;
}

beforeEach(function (): void {
    Artisan::call('permissions:refresh');
});

it('allows a role that holds the permission when the policy method passes', function (): void {
    expect(userWithRole('admin')->can('update', new Post))->toBeTrue();
});

it('denies a role that does not hold the permission', function (): void {
    expect(userWithRole('viewer')->can('update', new Post))->toBeFalse();
});

it('denies a user with no roles', function (): void {
    expect(userWithRole()->can('view', new Post))->toBeFalse();
});

it('lets the policy method refuse a role that holds the permission', function (): void {
    expect(userWithRole('admin')->can('update', new Post(['title' => 'locked'])))->toBeFalse();
});

it('authorizes class-string abilities', function (string $ability): void {
    expect(userWithRole('viewer')->can('viewAny', Post::class))->toBeTrue()
        ->and(userWithRole('editor')->can($ability, Post::class))->toBeTrue()
        ->and(userWithRole('viewer')->can('create', Post::class))->toBeFalse();
})->with(['viewAny', 'create']);

it('maps camelCase abilities to kebab-case permissions', function (): void {
    expect(Permission::where('name', 'post:update-eta')->exists())->toBeTrue()
        ->and(userWithRole('admin')->can('updateEta', new Post))->toBeTrue()
        ->and(userWithRole('admin')->can('update-eta', new Post))->toBeTrue();
});

it('denies rather than throws when an ability has no permission row', function (): void {
    expect(Permission::where('name', 'post:publish')->exists())->toBeFalse()
        ->and(userWithRole('admin')->can('publish', new Post))->toBeFalse();
});

it('keeps a user-assigned role and a stale cache from granting a revoked ability', function (): void {
    $user = userWithRole('editor');

    expect($user->can('view', new Post))->toBeTrue();

    $user->syncRoles('viewer');

    expect($user->fresh()->can('update', new Post))->toBeFalse();
});

it('derives the permission from the policy for a model authorized by a parent policy', function (): void {
    Gate::policy(Post::class, PostPolicy::class);

    expect(userWithRole('editor')->can('update', new FeaturedPost))->toBeTrue();
});

it('derives the permission from the policy when a differently named policy is registered', function (): void {
    Gate::policy(Customer::class, PostPolicy::class);

    expect(userWithRole('editor')->can('viewAny', Customer::class))->toBeTrue();
});

it('does not require App\Models\User', function (): void {
    config([
        'auth.guards.customer' => ['driver' => 'session', 'provider' => 'customers'],
        'auth.providers.customers' => ['driver' => 'eloquent', 'model' => Customer::class],
    ]);

    $customer = Customer::create(['name' => 'Panel user']);
    $role = Role::create(['name' => 'viewer', 'guard_name' => 'customer']);
    $permission = Permission::create(['name' => 'post:view', 'guard_name' => 'customer']);
    $role->givePermissionTo($permission);
    $customer->assignRole($role);

    expect(Gate::forUser($customer)->allows('view', new Post))->toBeTrue()
        ->and(Gate::forUser($customer)->allows('update', new Post))->toBeFalse();
});

it('only counts permissions held through a role', function (): void {
    $user = userWithRole('viewer');

    $user->givePermissionTo('post:update');

    expect($user->can('update', new Post))->toBeFalse();
});

it('checks a role on its own guard when the default guard changes mid-request', function (): void {
    config(['auth.guards.panel' => ['driver' => 'session', 'provider' => 'users']]);

    $user = userWithRole('editor');

    // What Filament's middleware does for a panel with its own authGuard().
    auth()->shouldUse('panel');

    expect($user->can('update', new Post))->toBeTrue();
});

it('lets a role with several memberships pass when only a later role holds the permission', function (): void {
    expect(userWithRole('viewer', 'editor')->can('update', new Post))->toBeTrue();
});

it('gives each single-table subclass the prefix of its own policy', function (): void {
    $this->usePolicyScenario('sti');
    Artisan::call('permissions:refresh');

    $technician = userWithRole('technician');

    expect($technician->can('view', new Service))->toBeTrue()
        ->and($technician->can('view', new Repair))->toBeTrue()
        ->and($technician->can('view', new Maintenance))->toBeFalse()
        ->and(Permission::where('name', 'maintenance:view')->exists())->toBeTrue();
});

it('passes extra Gate arguments through to the policy method', function (): void {
    $this->usePolicyScenario('multi-argument');
    Artisan::call('permissions:refresh');

    $parent = new Post(['title' => 'open']);

    expect(userWithRole('quoter')->can('deleteAny', [Item::class, $parent]))->toBeTrue()
        ->and(userWithRole('quoter')->can('deleteAny', [Item::class, new Post(['title' => 'locked'])]))->toBeFalse();
});

it('supports overriding before() and deferring to the parent', function (): void {
    $this->usePolicyScenario('overridden-before');
    Artisan::call('permissions:refresh');

    expect(userWithRole('operator')->can('view', new Module(['enabled' => true])))->toBeTrue()
        ->and(userWithRole('operator')->can('view', new Module(['enabled' => false])))->toBeFalse()
        ->and(userWithRole()->can('view', new Module(['enabled' => true])))->toBeFalse();
});

it('leaves a policy using only the contract and trait to its own methods', function (): void {
    $this->usePolicyScenario('trait-only');
    Artisan::call('permissions:refresh');

    expect(Permission::where('name', 'time-card:view')->exists())->toBeTrue()
        ->and(userWithRole()->can('view', new TimeCard))->toBeTrue();
});

it('survives being invoked with no arguments', function (): void {
    Gate::define('posts.update', [PostPolicy::class, 'viewAny']);

    expect(userWithRole('admin')->can('posts.update'))->toBeBool();
});

it('authorizes policies discovered in nested folders', function (): void {
    $this->usePolicyScenario('nested');
    Artisan::call('permissions:refresh');

    expect(class_exists(InvoicePolicy::class))->toBeTrue()
        ->and(Gate::getPolicyFor(Invoice::class))->toBeInstanceOf(InvoicePolicy::class)
        ->and(userWithRole('accountant')->can('view', new Invoice))->toBeTrue();
});

it('authorizes a model whose own name contains the word Policy', function (): void {
    $this->usePolicyScenario('policy-in-name');
    Artisan::call('permissions:refresh');

    expect(userWithRole('agent')->can('view', new InsurancePolicy))->toBeTrue();
});
