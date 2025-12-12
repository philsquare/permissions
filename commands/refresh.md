---
name: refresh
description: Sync all role permissions from policies to the database
---

Run the permissions refresh command to sync all role-permission mappings to the database:

```bash
php artisan permissions:refresh
```

This command:
1. Scans all policies in `app/Policies/` that extend `BasePolicy`
2. Reads the `rolePermissions()` method from each policy
3. Creates any missing roles in the database
4. Creates any missing permissions in the database
5. Syncs role-permission assignments (clears and rebuilds)
6. Clears the Spatie permission cache

Run this command after:
- Adding a new policy method
- Modifying `rolePermissions()` in any policy
- Adding a new role

After running, confirm the command completed successfully and report any errors to the user.
