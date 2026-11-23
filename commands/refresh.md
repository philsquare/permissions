---
name: refresh
description: Sync all role permissions from policies to the database
---

Run the permissions refresh command to sync all role-permission mappings to the database:

```bash
php artisan permissions:refresh
```

This command:
1. Finds every policy with a `rolePermissions()` method, in `app/Policies/` at any depth and any registered with `Gate::policy()`
2. Reads the `rolePermissions()` method from each policy
3. Creates any missing roles and permissions
4. Makes each role's permissions match the policies exactly, in one transaction. A role no policy declares ends up with no permissions
5. Clears the Spatie permission cache

If it finds no policies, it warns and changes nothing.

Run this command after:
- Adding a new policy method
- Modifying `rolePermissions()` in any policy
- Adding a new role

After running, confirm the command completed successfully and report any errors to the user.
