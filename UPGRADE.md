# Upgrading

## From 2.x to 3.0

3.0 needs the same PHP, Laravel and Spatie versions as 2.x.

```bash
composer require philsquare/permissions:^3.0
php artisan permissions:refresh
```

Your existing permission names don't change, unless a model's name contains the word "Policy" or a model is authorized by a differently named policy. Those two cases were broken before, and `permissions:refresh` creates the corrected names.

### Check your Filament access (high impact)

If you use Filament, some actions that everyone could use will now be restricted to the roles that should have them. This is the main reason for 3.0, and it may remove access people are used to. Review it before you deploy.

Filament allows an ability when a policy has no method for it, without running any role check. For policies extending `BasePolicy`:

| If the policy has no method for | The user now needs |
|---|---|
| `deleteAny`, `restore`, `forceDelete` | The permission for `delete` |
| `restoreAny` | The permission for `restore`, or for `delete` if the policy has no `restore()` |
| `forceDeleteAny` | The permission for `forceDelete`, or for `delete` if the policy has no `forceDelete()` |
| `replicate` | The permission for `create` |
| `reorder` | The permission for `update` |
| `viewAny`, `view`, `create`, `update`, `delete` | Denied for everyone, until the policy defines it |

A role granted `all()` keeps these actions as long as the policy defines `delete()`, `create()` and `update()`.

Check three things:

1. **Roles that should keep an action.** A role with `only(['viewAny', 'view', 'update'])` loses bulk delete, restore and permanent delete. Add `delete` to the role if it should keep them.
2. **Roles that shouldn't get an action.** A role with `delete` can now restore and permanently delete unless the policy defines `restore()` and `forceDelete()`, which makes each a permission of its own.
3. **Policies with missing methods.** If a resource uses `EditAction`, for example, and its policy has no `update()` method, nobody can edit until you add one.

Bulk actions still skip any record-level logic in your policy methods. See [Bulk actions and record rules](README.md#bulk-actions-and-record-rules).

### Update any `before()` override

`before()` no longer requires `App\Models\User`. If you override it, change the parameter types or PHP will fail to load the class:

```php
// Before
public function before(User $user, string $ability, $arguments): ?bool

// After
use Illuminate\Contracts\Auth\Authenticatable;

public function before(Authenticatable $user, string $ability, mixed $arguments = null): ?bool
```

The method body and the call to `parent::before()` can stay as they are. The `$arguments` parameter still holds the model or class being authorized.

### Replace Collection calls on `crud()`

`crud()` now returns an array. This only affects you if you chain Collection methods onto it:

```php
// Before
$this->permissions()->crud()->merge(['publish'])

// After
$this->permissions()->crud(['publish'])
```

### Replace `Enum\Roles`

`Philsquare\Permissions\Enum\Roles` has been removed. If you use it, define your own enum. The README has an example.

### `permissions:refresh` output and behaviour

The command now prints a summary, and it warns and changes nothing if it finds no policies. If you run `permission:cache-reset` first to avoid a stale cache error, you no longer need to.

## From 1.x to 3.0

Follow the steps above, plus:

1. 3.0 needs PHP 8.4 and Laravel 12 or later.
2. Move to `spatie/laravel-permission` ^8.3 at the same time. If your `composer.json` pins it, update both together:

   ```bash
   composer require philsquare/permissions:^3.0 spatie/laravel-permission:^8.3 -W
   ```

3. Work through [Spatie's upgrade guide](https://spatie.be/docs/laravel-permission/v8/upgrading). Nothing in this package depends on what it changes, but your app might, particularly if it listens for Spatie's events or extends its models.
4. Run `php artisan permissions:refresh`. Rows named like `post:before` from older versions stay in the `permissions` table. They aren't granted to anyone and can be deleted.
