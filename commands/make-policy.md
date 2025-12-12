---
name: make-policy
description: Create a new Laravel policy with philsquare/permissions role-based permissions
arguments:
  - name: model
    description: The model name for the policy (e.g., Post, Order, User)
    required: true
---

Create a new Laravel policy for the model `$model` with role-based permissions using philsquare/permissions.

## Steps

1. First, check if the policy already exists at `app/Policies/{$model}Policy.php`
2. If it exists, ask the user if they want to add permissions to the existing policy
3. Create the policy file with the following structure:

```php
<?php

namespace App\Policies;

use App\Models\{$model};
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class {$model}Policy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'admin' => $this->permissions()->all(),
        ];
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, {$model} $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, {$model} $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, {$model} $model): bool
    {
        return true;
    }
}
```

4. Check if `app/Enums/Roles.php` exists and use it in rolePermissions() if available
5. Register the policy in `app/Providers/AuthServiceProvider.php` if it uses explicit registration
6. Remind the user to:
   - Customize the `rolePermissions()` method with appropriate roles
   - Update policy methods with business logic
   - Run `php artisan permissions:refresh` to sync permissions

## Notes

- Use PascalCase for the model name
- The policy will extend `BasePolicy` from philsquare/permissions
- Policy methods return `true` by default - add business logic as needed
- Role permission checks happen automatically in the `before()` hook
