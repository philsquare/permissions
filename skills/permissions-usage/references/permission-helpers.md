# Permission Helpers Reference

Complete reference for the `$this->permissions()` helper available in policies extending `BasePolicy`.

## How Permissions Service Works

The `permissions()` method returns a `Permissions` service instance that introspects the policy class to find all public methods. It automatically excludes system methods:

**Ignored methods:**
- `denyWithStatus`
- `denyAsNotFound`
- `allow`
- `deny`
- `permissions`
- `rolePermissions`

## Method Reference

### `all(): array`

Returns all public methods from the policy class, excluding ignored system methods.

**Use case:** Give a role full access to all actions on a resource.

```php
public function rolePermissions(): array
{
    return [
        'admin' => $this->permissions()->all(),
    ];
}
```

**Example output for a policy with viewAny, view, create, update, delete, publish, archive:**
```php
['viewAny', 'view', 'create', 'update', 'delete', 'publish', 'archive']
```

### `crud(array $additionalMethods = []): array`

Returns the standard CRUD methods plus any additional methods specified.

**Default CRUD methods:**
- `viewAny`
- `view`
- `create`
- `update`
- `delete`

**Use case:** Give a role standard CRUD access plus specific additional actions.

```php
// Just CRUD
'editor' => $this->permissions()->crud(),

// CRUD plus custom actions
'manager' => $this->permissions()->crud(['approve', 'publish']),
```

**Example with additionalMethods:**
```php
$this->permissions()->crud(['approve', 'reject'])
// Returns: ['viewAny', 'view', 'create', 'update', 'delete', 'approve', 'reject']
```

### `only(array $methods): array`

Returns only the specified methods from the policy.

**Use case:** Restrict a role to specific actions only.

```php
// Read-only access
'viewer' => $this->permissions()->only(['viewAny', 'view']),

// Specific operations only
'approver' => $this->permissions()->only(['view', 'approve', 'reject']),
```

**Note:** Methods not defined on the policy will be silently excluded from the result.

### `except(array $methods): array`

Returns all methods except the specified ones.

**Use case:** Give a role most permissions but exclude dangerous ones.

```php
// Everything except destructive actions
'editor' => $this->permissions()->except(['delete', 'forceDelete']),

// Everything except admin-only actions
'support' => $this->permissions()->except(['create', 'delete', 'forceDelete', 'restore']),
```

### `permissionList(): Collection`

Returns the underlying Laravel Collection of permission methods. Useful for custom filtering.

```php
// Custom filtering
$methods = $this->permissions()
    ->permissionList()
    ->filter(fn ($method) => str_starts_with($method, 'view'))
    ->toArray();
```

## Common Patterns

### Tiered Access

```php
public function rolePermissions(): array
{
    return [
        'admin' => $this->permissions()->all(),
        'manager' => $this->permissions()->except(['forceDelete']),
        'editor' => $this->permissions()->crud(),
        'viewer' => $this->permissions()->only(['viewAny', 'view']),
    ];
}
```

### Feature-Based Access

```php
public function rolePermissions(): array
{
    return [
        'admin' => $this->permissions()->all(),
        'sales' => $this->permissions()->only([
            'viewAny', 'view', 'create', 'update',
            'generateQuote', 'sendProposal',
        ]),
        'finance' => $this->permissions()->only([
            'viewAny', 'view',
            'processPayment', 'issueRefund', 'generateInvoice',
        ]),
        'support' => $this->permissions()->only([
            'viewAny', 'view',
            'addNote', 'escalate',
        ]),
    ];
}
```

### Using Enums for Roles

```php
use App\Enums\Roles;

public function rolePermissions(): array
{
    return [
        Roles::Admin->value => $this->permissions()->all(),
        Roles::Manager->value => $this->permissions()->crud(['approve']),
        Roles::Staff->value => $this->permissions()->only(['viewAny', 'view']),
    ];
}
```

## Complete Policy Example

```php
<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\Roles;
use App\Models\Order;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class OrderPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            Roles::Admin->value => $this->permissions()->all(),
            Roles::Manager->value => $this->permissions()->crud([
                'approve',
                'cancel',
                'refund',
            ]),
            Roles::Sales->value => $this->permissions()->only([
                'viewAny',
                'view',
                'create',
                'update',
            ]),
            Roles::Support->value => $this->permissions()->only([
                'viewAny',
                'view',
                'addNote',
            ]),
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
        return $order->status === OrderStatus::PENDING;
    }

    public function delete(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::DRAFT
            && $order->items()->count() === 0;
    }

    public function approve(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::PENDING_APPROVAL;
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

## Troubleshooting

### Permission not working

1. Verify `permissions:refresh` has been run after adding the method
2. Check the user has the role assigned: `$user->hasRole('admin')`
3. Verify the permission exists: `Permission::where('name', 'order:approve')->exists()`
4. Check permission naming: method `updateEta` becomes `model:update-eta`

### Method not appearing in permissions

Ensure the method:
- Is public
- Is not in the ignored list
- Is defined on the policy class (not inherited from BasePolicy)
- Policy extends `BasePolicy`

### Role not being created

Ensure:
- Role name in `rolePermissions()` matches expected format (lowercase, kebab-case)
- `permissions:refresh` completed without errors
- Database connection is working
