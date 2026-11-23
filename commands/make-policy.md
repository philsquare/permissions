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
3. If it doesn't, generate it with the package's command:

```bash
php artisan make:policy {$model}Policy --model={$model} --withPermissions
```

4. Fill in `rolePermissions()`. Check if a roles enum exists, such as `app/Enums/Roles.php`, and use it if so
5. Remind the user to:
   - Update the policy methods with business logic, since they all return `false` until then
   - Run `php artisan permissions:refresh` to sync permissions

## Notes

- Use PascalCase for the model name
- The policy will extend `BasePolicy` from philsquare/permissions
- The policy defines every ability Filament uses, including `deleteAny`, `restoreAny`, `forceDeleteAny`, `replicate` and `reorder`. Each is its own permission, so grant the ones a role needs: `crud(['deleteAny'])`
- Laravel discovers policies automatically, so there is nothing to register
- Role permission checks happen automatically in the `before()` hook
