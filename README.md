# Philsquare Permissions

A Laravel package for managing role-based permissions through policies. Define permissions declaratively in your policies and sync them to your database with a single command.

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission) ^8.3
- [Filament](https://filamentphp.com) 5 is optional, and [supported with no setup](#filament)

Upgrading from an earlier version? See [UPGRADE.md](UPGRADE.md).

## Installation

```bash
composer require philsquare/permissions
```

The service provider is auto-registered via Laravel's package discovery.

### Setup Spatie Permissions

If you haven't already, publish and run the Spatie migrations:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Add the `HasRoles` trait to your User model:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

## Quick Start

### 1. Create a Policy

Use the artisan command with the `--withPermissions` flag:

```bash
php artisan make:policy PostPolicy --model=Post --withPermissions
```

Or add permissions to an existing policy by extending `BasePolicy`:

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class PostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'admin' => $this->permissions()->all(),
            'editor' => $this->permissions()->crud(),
            'viewer' => $this->permissions()->only(['viewAny', 'view']),
        ];
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return true;
    }

    public function delete(User $user, Post $post): bool
    {
        return true;
    }
}
```

### 2. Sync Permissions to Database

Run the refresh command whenever you add or modify permissions:

```bash
php artisan permissions:refresh
```

This command:
- Finds every policy with a `rolePermissions()` method, in `app/Policies/` at any depth and anywhere else you've registered one with `Gate::policy()`
- Creates any missing roles and permissions
- Makes each role's permissions match your policies exactly

The sync runs in one transaction, so requests keep seeing the old permissions until the new ones are ready, and a failure changes nothing. A role that no policy declares ends up with no permissions.

### 3. Assign Roles to Users

Use Spatie's methods to assign roles:

```php
$user->assignRole('editor');
```

### 4. Check Permissions

Use Laravel's built-in authorization:

```php
// Using Gate
Gate::authorize('update', $post);

// On User model
$user->can('update', $post);

// In Blade
@can('update', $post)
    <button>Edit</button>
@endcan
```

## How It Works

### Permission Naming Convention

Permissions are stored in kebab-case format: `{model}:{action}`

| Policy Method | Database Permission |
|---------------|---------------------|
| `PostPolicy::viewAny()` | `post:view-any` |
| `PostPolicy::create()` | `post:create` |
| `PostPolicy::forceDelete()` | `post:force-delete` |
| `PurchaseOrderPolicy::updateEta()` | `purchase-order:update-eta` |

The first part comes from the policy's class name, without its folder. `App\Policies\Billing\InvoicePolicy` uses `invoice:*`, so two policies with the same name in different folders share their permissions.

### The `before()` Hook

When you extend `BasePolicy`, the `before()` method automatically checks if the user's roles have the required permission. If the permission exists, it returns `null` (allowing the policy method to execute). If not, it returns `false` (denying access).

This means your policy methods define the *logic* for when an action should be allowed, and the role-permission mapping controls *who* can attempt it.

Only permissions held through a role count. A permission given directly to a user with `givePermissionTo()` doesn't pass this check.

To add your own check in front of the role check, override `before()` and call the parent:

```php
use Illuminate\Contracts\Auth\Authenticatable;

public function before(Authenticatable $user, string $ability, mixed $subject = null): ?bool
{
    if (! Feature::active('orders')) {
        return false;
    }

    return parent::before($user, $ability, $subject);
}
```

### Policy Method Return Values

Your policy methods should return `true` or `false` based on business logic:

```php
public function update(User $user, Post $post): bool
{
    // Only allow updates if post is draft OR user has force-update permission
    return $post->status === 'draft' || $user->can('force-update', $post);
}
```

The `before()` hook runs first. If the user lacks the permission, they're denied immediately. If they have the permission, your method's logic determines the final result.

## Permission Helpers

The `permissions()` method provides helpers for building permission lists. Each one returns an array.

Helpers take policy method names exactly as written, so use `deleteDraft`, not `delete-draft`. A name that doesn't match a method is dropped without warning, which leaves the role without that permission.

### `all()`

Returns all public methods from the policy (excluding system methods):

```php
public function rolePermissions(): array
{
    return [
        'admin' => $this->permissions()->all(),
    ];
}
```

### `crud(array $additional = [])`

Returns standard CRUD methods plus any additional methods:

```php
// Returns: viewAny, view, create, update, delete
'editor' => $this->permissions()->crud(),

// Returns: viewAny, view, create, update, delete, publish, archive
'editor' => $this->permissions()->crud(['publish', 'archive']),
```

### `only(array $methods)`

Returns only the specified methods:

```php
'viewer' => $this->permissions()->only(['viewAny', 'view']),
```

### `except(array $methods)`

Returns all methods except the specified ones:

```php
'editor' => $this->permissions()->except(['delete', 'forceDelete']),
```

## Full Example

```php
<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class OrderPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'admin' => $this->permissions()->all(),
            'manager' => $this->permissions()->crud(['cancel', 'refund']),
            'sales' => $this->permissions()->only(['viewAny', 'view', 'create']),
            'support' => $this->permissions()->only(['viewAny', 'view', 'addNote']),
        ];
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        // Can only update pending orders
        return $order->status === OrderStatus::PENDING;
    }

    public function delete(User $user, Order $order): bool
    {
        // Can only delete draft orders with no items
        return $order->status === OrderStatus::DRAFT
            && $order->items()->count() === 0;
    }

    public function cancel(User $user, Order $order): bool
    {
        return in_array($order->status, [
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
        ]);
    }

    public function refund(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::COMPLETED
            && $order->paid_at !== null;
    }

    public function addNote(User $user, Order $order): bool
    {
        return true;
    }
}
```

## Filament

The package works with Filament 5 panels as they are, but Filament treats policies differently from plain Laravel in two ways that matter here.

### Abilities a policy doesn't define

When a policy has no method for an ability, Filament doesn't call the policy, so `before()` never runs. It allows the action instead. A role with no delete access would still be able to use the `DeleteBulkAction` in every generated resource.

The package closes this for policies extending `BasePolicy`:

| Ability the policy leaves out | What the user needs |
|---|---|
| `deleteAny`, `restore`, `forceDelete` | The permission for `delete` |
| `restoreAny` | The permission for `restore`, or for `delete` if the policy has no `restore()` |
| `forceDeleteAny` | The permission for `forceDelete`, or for `delete` if the policy has no `forceDelete()` |
| `replicate` | The permission for `create` |
| `reorder` | The permission for `update` |
| `viewAny`, `view`, `create`, `update`, `delete` | Denied until the policy defines it |

So a role that can delete can also restore and permanently delete, until you say otherwise. To separate them, define `forceDelete()` on the policy, which makes it a permission of its own.

An app's own `Gate::before()` callback, such as a super admin check, still takes priority. So does any ability you've defined with `Gate::define()`.

Policies generated with `--withPermissions` define all of these abilities, so each one is its own permission that you grant explicitly. That also means they work with Filament's `strictAuthorization()`:

```php
'editor' => $this->permissions()->crud(['deleteAny', 'reorder']),
```

### Bulk actions and record rules

Filament checks `deleteAny` once for the whole selection, so any record-level logic in your `delete()` method doesn't run for bulk deletes. If `delete()` has rules like "only drafts", ask Filament to check each record:

```php
DeleteBulkAction::make()->authorizeIndividualRecords();
```

Or turn this on for every bulk delete in a service provider:

```php
DeleteBulkAction::configureUsing(
    fn (DeleteBulkAction $action) => $action->authorizeIndividualRecords()
);
```

## Commands

### `permissions:refresh`

Syncs all role permissions from policies to the database. Run this after:
- Adding a new policy method
- Changing the `rolePermissions()` array
- Adding a new role

```bash
php artisan permissions:refresh
```

If it finds no policies, it warns you and changes nothing.

### `make:policy --withPermissions`

Creates a new policy that extends `BasePolicy`, with a method for every ability Filament uses. `--model` is required, because the generated methods need it:

```bash
php artisan make:policy ArticlePolicy --model=Article --withPermissions
```

## Deployment

Add `permissions:refresh` to your deploy script to ensure permissions stay in sync:

```bash
php artisan migrate --force
php artisan permissions:refresh
php artisan config:cache
php artisan route:cache
```

This ensures any new permissions added in the release are automatically synced to the database without manual intervention.

## Defining Roles

Roles are created automatically when you run `permissions:refresh`. Any role name used in a `rolePermissions()` array will be created if it doesn't exist.

For better organization, you can define roles in an enum:

```php
<?php

namespace App\Enums;

enum Roles: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Editor = 'editor';
    case Viewer = 'viewer';
}
```

Then use it in your policies:

```php
use App\Enums\Roles;

public function rolePermissions(): array
{
    return [
        Roles::Admin->value => $this->permissions()->all(),
        Roles::Editor->value => $this->permissions()->crud(),
    ];
}
```

## Claude Code Plugin

This package includes a [Claude Code](https://claude.com/claude-code) plugin that provides AI-assisted permission implementation.

### Features

**Autonomous Implementation**: When you create a new policy in a project using this package, the plugin's agent will proactively offer to add role-based permissions - extending `BasePolicy` and implementing `rolePermissions()` with appropriate helpers.

**Skill Knowledge**: The plugin provides Claude with knowledge about permission helpers, naming conventions, and best practices so it can correctly implement permissions without documentation lookups.

**Commands**:
- `/philsquare-permissions:refresh` - Run `permissions:refresh` to sync permissions
- `/philsquare-permissions:make-policy <Model>` - Create a new policy with permissions scaffold

### Installation

The plugin lives in this package's GitHub repository, which is also a plugin marketplace. In Claude Code, run:

```
/plugin marketplace add philsquare/permissions
/plugin install philsquare-permissions@philsquare-permissions
```

To set it up for everyone working on a project, commit this to the project's `.claude/settings.json` instead:

```json
{
  "extraKnownMarketplaces": {
    "philsquare-permissions": {
      "source": {
        "source": "github",
        "repo": "philsquare/permissions"
      }
    }
  },
  "enabledPlugins": {
    "philsquare-permissions@philsquare-permissions": true
  }
}
```

The plugin comes from the repository's default branch, so it describes the latest release.

### Plugin Structure

```
.claude-plugin/
├── plugin.json           # Plugin manifest
commands/
├── refresh.md            # Refresh command
├── make-policy.md        # Make policy command
agents/
├── permissions-implementer.md  # Autonomous agent
skills/
└── permissions-usage/
    ├── SKILL.md          # Package knowledge
    └── references/
        └── permission-helpers.md
```

### Using the Plugin

Once the plugin is enabled, Claude uses it when you work on policies. When creating policies, Claude will:

1. Detect the package is installed
2. Extend `BasePolicy` instead of Laravel's base policy
3. Add the `rolePermissions()` method with appropriate role mappings
4. Remind you to run `permissions:refresh`

## License

MIT
