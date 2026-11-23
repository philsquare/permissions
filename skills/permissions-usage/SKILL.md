---
name: Philsquare Permissions
description: This skill should be used when the user asks to "add permissions to a policy", "create a policy with roles", "set up role permissions", "configure rolePermissions", "use BasePolicy", or when working with Laravel policies that need role-based permission management. Also activate when creating or modifying policies in a project using philsquare/permissions.
---

# Philsquare Permissions Package

This skill provides guidance for implementing role-based permissions through Laravel policies using the philsquare/permissions package.

## Package Detection

Check if the project uses this package by looking for:
- `philsquare/permissions` in `composer.json`
- Policies extending `Philsquare\Permissions\BasePolicy`
- A `rolePermissions()` method in existing policies

## Core Concepts

### BasePolicy

All policies requiring role-based permissions must extend `BasePolicy`:

```php
use Philsquare\Permissions\BasePolicy;

class PostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'admin' => $this->permissions()->all(),
        ];
    }

    // Policy methods...
}
```

### How Authorization Works

1. User calls `$user->can('update', $post)` or `Gate::authorize('update', $post)`
2. Laravel routes to the policy's `update()` method
3. `BasePolicy::before()` runs first, checking if user's roles have the permission
4. If permission exists (`post:update`), the policy method executes
5. If permission missing, access denied immediately

The `before()` hook converts:
- Policy class: `PostPolicy` to `post` (folders are ignored, so `Billing\InvoicePolicy` is `invoice`)
- Method: `updateEta` to `update-eta`
- Combined: `post:update-eta`

Only permissions held through a role count. One given directly to a user with `givePermissionTo()` doesn't pass this check.

### Permission Naming Convention

Permissions are stored in kebab-case: `{model}:{action}`

| Policy Method | Database Permission |
|---------------|---------------------|
| `viewAny()` | `model:view-any` |
| `forceDelete()` | `model:force-delete` |
| `updateEta()` | `model:update-eta` |

## The rolePermissions() Method

Define which roles can access which policy methods:

```php
public function rolePermissions(): array
{
    return [
        'admin' => $this->permissions()->all(),
        'manager' => $this->permissions()->crud(['approve', 'reject']),
        'editor' => $this->permissions()->only(['viewAny', 'view', 'update']),
        'viewer' => $this->permissions()->only(['viewAny', 'view']),
    ];
}
```

## Permission Helpers

Access via `$this->permissions()` in any policy extending BasePolicy. Each helper returns an array.

Helpers take method names exactly as written: `deleteDraft`, not `delete-draft`. A name that matches no method is dropped without warning, leaving the role without that permission.

### `all()`
Returns all public, non-static policy methods, excluding `before`, `after`, `rolePermissions` and magic methods.

### `crud(array $additional = [])`
Returns: `viewAny`, `view`, `create`, `update`, `delete` plus any additional methods.

```php
$this->permissions()->crud(['publish', 'archive'])
// Returns: viewAny, view, create, update, delete, publish, archive
```

### `only(array $methods)`
Returns only the specified methods.

```php
$this->permissions()->only(['viewAny', 'view'])
```

### `except(array $methods)`
Returns all methods except specified ones.

```php
$this->permissions()->except(['delete', 'forceDelete'])
```

## Policy Method Implementation

Policy methods define business logic for when actions are allowed:

```php
public function update(User $user, Order $order): bool
{
    // Role check happens in before() hook
    // This method handles additional business logic
    return $order->status === 'draft';
}
```

Return `true` to allow, `false` to deny. The role permission check happens first in `before()`.

## Syncing Permissions

After modifying policies, run:

```bash
php artisan permissions:refresh
```

This finds every policy with a `rolePermissions()` method, in `app/Policies/` at any depth and any registered with `Gate::policy()`, and makes the database match in one transaction. A role no policy declares ends up with no permissions.

**Add to deployment scripts** to auto-sync on deploy.

## Filament

Filament doesn't call a policy for an ability it has no method for, and allows the action. The package closes this for policies extending `BasePolicy`:

| Ability left out of the policy | The user needs |
|---|---|
| `deleteAny`, `restore`, `forceDelete` | The permission for `delete` |
| `restoreAny` | The permission for `restore`, or `delete` if the policy has no `restore()` |
| `forceDeleteAny` | The permission for `forceDelete`, or `delete` if the policy has no `forceDelete()` |
| `replicate` | The permission for `create` |
| `reorder` | The permission for `update` |
| `viewAny`, `view`, `create`, `update`, `delete` | Denied until the policy defines it |

When a policy defines one of these, it's a permission of its own, and `crud()` doesn't include the bulk ones. Policies from `make:policy --withPermissions` define all of them, so grant them explicitly: `crud(['deleteAny'])`.

Bulk actions run once for the whole selection, so record-level rules in `delete()` don't apply unless the action uses `->authorizeIndividualRecords()`.

## Defining Roles

Roles are created automatically when `permissions:refresh` runs. Use an enum for type safety:

```php
namespace App\Enums;

enum Roles: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Editor = 'editor';
}
```

Use in policies:

```php
use App\Enums\Roles;

public function rolePermissions(): array
{
    return [
        Roles::Admin->value => $this->permissions()->all(),
    ];
}
```

## Additional Resources

For detailed helper documentation, consult:
- **`references/permission-helpers.md`** - Complete helper method reference with examples
