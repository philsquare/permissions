<?php

use App\Policies\PostPolicy;
use Philsquare\Permissions\Services\Permissions;

function helpers(): Permissions
{
    return new Permissions(PostPolicy::class);
}

it('lists every ability a policy defines', function (): void {
    expect(helpers()->all())
        ->toEqualCanonicalizing(['viewAny', 'view', 'create', 'update', 'delete', 'updateEta', 'publish']);
});

it('returns a list from every helper, as the docs promise', function (string $helper, array $arguments): void {
    $result = helpers()->{$helper}(...$arguments);

    expect($result)->toBeArray()->and(array_is_list($result))->toBeTrue();
})->with([
    'all' => ['all', []],
    'crud' => ['crud', []],
    'crud with additions' => ['crud', [['publish']]],
    'only' => ['only', [['view', 'delete']]],
    'except' => ['except', [['view']]],
]);

it('filters with only and except', function (): void {
    expect(helpers()->only(['view', 'missing']))->toBe(['view'])
        ->and(helpers()->except(['publish', 'updateEta', 'delete']))
        ->toEqualCanonicalizing(['viewAny', 'view', 'create', 'update']);
});

it('adds additional methods to crud without repeating any', function (): void {
    expect(helpers()->crud(['publish', 'view']))
        ->toEqualCanonicalizing(['viewAny', 'view', 'create', 'update', 'delete', 'publish']);
});
