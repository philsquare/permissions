# Philsquare Permissions

A Laravel package for managing role-based permissions through policies. Define permissions declaratively in your policies and sync them to your database with a single command.

## Requirements

- PHP 8.1+
- Laravel 10+
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission) ^6.21

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
- Scans all policies in `app/Policies/` that extend `BasePolicy`
- Creates any missing roles
- Creates any missing permissions
- Syncs role-permission assignments

### 3. Assign Roles to Users

Use Spatie's methods to assign roles:

```php
$user->assignRole('editor');
```

### 4. Check Permissions

Use Laravel's built-in authorization:

```php
// In controllers
$this->authorize('update', $post);

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

### The `before()` Hook

When you extend `BasePolicy`, the `before()` method automatically checks if the user's roles have the required permission. If the permission exists, it returns `null` (allowing the policy method to execute). If not, it returns `false` (denying access).

This means your policy methods define the *logic* for when an action should be allowed, and the role-permission mapping controls *who* can attempt it.

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

The `permissions()` method provides helpers for building permission lists:

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

## Commands

### `permissions:refresh`

Syncs all role permissions from policies to the database. Run this after:
- Adding a new policy method
- Changing the `rolePermissions()` array
- Adding a new role

```bash
php artisan permissions:refresh
```

### `make:policy --withPermissions`

Creates a new policy that extends `BasePolicy`:

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

The plugin is included in the package. To enable it in Claude Code:

**Option 1: Using the /plugins command**

Run `/plugins` in Claude Code and add `vendor/philsquare/permissions` as a local plugin.

**Option 2: Manual configuration**

Add the plugin path to your project's `.claude/settings.json`:

```json
{
  "plugins": [
    "vendor/philsquare/permissions"
  ]
}
```

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

The plugin activates automatically in projects that have `philsquare/permissions` in their `composer.json`. When creating policies, Claude will:

1. Detect the package is installed
2. Extend `BasePolicy` instead of Laravel's base policy
3. Add the `rolePermissions()` method with appropriate role mappings
4. Remind you to run `permissions:refresh`

## License

MIT
