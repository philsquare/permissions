<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Philsquare\Permissions\Tests\Fixtures\BulkPostPolicy;
use Philsquare\Permissions\Tests\Fixtures\Filament\PostResource;
use Philsquare\Permissions\Tests\Fixtures\PlainPolicy;
use Philsquare\Permissions\Tests\Fixtures\SoftDeletingPostPolicy;
use Philsquare\Permissions\Tests\Fixtures\ViewOnlyPostPolicy;
use Spatie\Permission\Models\Role;

/*
 * PostPolicy defines no deleteAny(), forceDeleteAny(), restoreAny(), replicate()
 * or reorder(). That is the shape Laravel's own stub generates, and what
 * make:filament-resource's DeleteBulkAction runs into.
 */
beforeEach(function (): void {
    Artisan::call('permissions:refresh');
});

function actingAsRole(string $role): void
{
    $user = User::create(['name' => $role, 'email' => fake()->unique()->safeEmail()]);
    $user->assignRole($role);

    test()->actingAs($user);
}

it('runs the role check for abilities the policy defines', function (): void {
    actingAsRole('viewer');

    expect(PostResource::canViewAny())->toBeTrue()
        ->and(PostResource::canCreate())->toBeFalse()
        ->and(PostResource::canDelete(new Post))->toBeFalse();
});

it('does not let a role without delete access bulk delete', function (): void {
    actingAsRole('viewer');

    expect(PostResource::canDeleteAny())->toBeFalse();
});

it('keeps bulk delete for a role holding delete', function (): void {
    actingAsRole('editor');

    expect(PostResource::canDeleteAny())->toBeTrue();
});

it('does not let a role without the base ability use undefined Filament abilities', function (string $method, array $arguments): void {
    actingAsRole('viewer');

    expect(PostResource::{$method}(...$arguments))->toBeFalse();
})->with([
    'forceDeleteAny' => ['canForceDeleteAny', []],
    'restoreAny' => ['canRestoreAny', []],
    'reorder' => ['canReorder', []],
    'replicate' => fn () => ['canReplicate', [new Post]],
    'restore' => fn () => ['canRestore', [new Post]],
    'forceDelete' => fn () => ['canForceDelete', [new Post]],
]);

it('keeps replicate and reorder for roles holding create and update', function (): void {
    actingAsRole('editor');

    expect(PostResource::canReorder())->toBeTrue()
        ->and(PostResource::canReplicate(new Post))->toBeTrue();
});

it('keeps restore and force delete for a role holding delete when the policy leaves them out', function (): void {
    actingAsRole('editor');

    expect(PostResource::canRestore(new Post))->toBeTrue()
        ->and(PostResource::canForceDelete(new Post))->toBeTrue()
        ->and(PostResource::canRestoreAny())->toBeTrue()
        ->and(PostResource::canForceDeleteAny())->toBeTrue();
});

it('lets a policy make restore a permission of its own by defining it', function (): void {
    Gate::policy(Post::class, SoftDeletingPostPolicy::class);
    Artisan::call('permissions:refresh');

    actingAsRole('editor');
    expect(PostResource::canRestoreAny())->toBeFalse()
        ->and(PostResource::canForceDeleteAny())->toBeTrue();

    actingAsRole('restorer');
    expect(PostResource::canRestoreAny())->toBeTrue()
        ->and(PostResource::canForceDeleteAny())->toBeFalse();
});

it('denies what nothing stands in for, whatever the role holds', function (): void {
    Gate::policy(Post::class, ViewOnlyPostPolicy::class);
    Artisan::call('permissions:refresh');

    actingAsRole('reader');

    expect(PostResource::canViewAny())->toBeTrue()
        ->and(PostResource::canCreate())->toBeFalse()
        ->and(PostResource::canEdit(new Post))->toBeFalse()
        ->and(PostResource::canDelete(new Post))->toBeFalse()
        ->and(PostResource::canDeleteAny())->toBeFalse()
        ->and(PostResource::canRestore(new Post))->toBeFalse()
        ->and(PostResource::canRestoreAny())->toBeFalse()
        ->and(PostResource::canForceDelete(new Post))->toBeFalse()
        ->and(PostResource::canForceDeleteAny())->toBeFalse()
        ->and(PostResource::canReplicate(new Post))->toBeFalse()
        ->and(PostResource::canReorder())->toBeFalse();
});

it('lets an app super admin past undefined abilities', function (): void {
    Role::create(['name' => 'super-admin']);

    actingAsRole('super-admin');

    expect(PostResource::canDeleteAny())->toBeTrue()
        ->and(PostResource::canForceDelete(new Post))->toBeTrue();
});

it('does not touch abilities defined with Gate::define', function (): void {
    Gate::define('reorder', fn () => true);

    actingAsRole('viewer');

    expect(PostResource::canReorder())->toBeTrue();
});

it('does not touch models whose policy does not extend BasePolicy', function (): void {
    Gate::policy(Post::class, PlainPolicy::class);

    actingAsRole('viewer');

    expect(PostResource::canDeleteAny())->toBeTrue();
});

it('leaves a policy that defines the ability to its own role check', function (): void {
    Gate::policy(Post::class, BulkPostPolicy::class);
    Artisan::call('permissions:refresh');

    actingAsRole('editor');
    expect(PostResource::canDeleteAny())->toBeFalse();

    actingAsRole('bulk-editor');
    expect(PostResource::canDeleteAny())->toBeTrue();
});
