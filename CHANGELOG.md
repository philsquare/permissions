# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-11-23

See [UPGRADE.md](UPGRADE.md) for what to change.

### Security
- Filament no longer bypasses role checks for abilities a policy doesn't define.
  Filament allows these without calling the policy, so `before()` never ran: a
  role with no delete access could use `DeleteBulkAction`, and likewise force
  delete, restore, replicate and reorder. For policies extending `BasePolicy`,
  `deleteAny`, `restore` and `forceDelete` now need the permission for `delete`,
  `restoreAny` and `forceDeleteAny` need the permission for `restore` and
  `forceDelete` (or `delete` if the policy leaves those out too), `replicate`
  needs `create`, `reorder` needs `update`, and any other Filament ability is
  denied until the policy defines it. Apps with no Filament installed are
  unaffected.

### Changed
- **BREAKING**: `BasePolicy::before()` is now
  `before(Authenticatable $user, string $ability, mixed $subject = null)`. It no
  longer hard-codes `App\Models\User`, which raised a `TypeError` for any other
  user model. Overrides must match the new parameter types.
- **BREAKING**: `Permissions::crud()` returns an array, as documented, rather than
  a Collection. `only()`, `except()` and `crud()` now return lists with no gaps in
  their keys.
- `before()` builds the permission from the policy rather than from the model it
  was handed. A subclass authorized by its parent's policy, or a policy registered
  for a model with a different name, was always denied because the two names
  disagreed.
- `make:policy --withPermissions` without `--model` now fails with an explanation.
  It used to ignore the flag, producing a policy with no role checks.
- The generated policy defines every ability Filament uses, adding `deleteAny`,
  `restoreAny`, `forceDeleteAny`, `replicate` and `reorder`, and no longer imports
  an unused `Response` class.
- `laravel/framework` is now an explicit dependency. The package extends a
  framework class and relied on Spatie to pull it in.

### Removed
- **BREAKING**: `Philsquare\Permissions\Enum\Roles`. Nothing in the package used it.
  Define a roles enum in your app, as the README shows.

### Fixed
- `make:policy` works on Laravel 13.24 and later, where it crashed with `The
  "withPermissions" option does not exist` whenever `--model` was given or the
  command prompted for its arguments. Laravel moved the command from `getOptions()`
  to `$signature`, so the package's option was never registered.
- `make:policy` no longer asks about permissions when options were already passed.
  Its answer used to overwrite them.
- `permissions:refresh` runs in a transaction. It used to truncate the pivot table
  first, so every user lost access until it finished, and for good if it failed.
- `permissions:refresh` no longer fails on a repeated permission, such as
  `crud(['view'])`, or when run twice in one process. Either raised a unique
  constraint error after the table had already been truncated.
- `permissions:refresh` changes nothing when it finds no policies, rather than
  removing every grant.
- `permissions:refresh` finds policies in nested folders, which Laravel auto-discovers
  but this command skipped, leaving them denied for everyone. It also picks up
  policies registered with `Gate::policy()` outside `app/Policies`.
- `permissions:refresh` skips abstract policies, resolves policies through the
  container so they can have constructor dependencies, and uses the app's namespace.
- `permissions:refresh` follows Spatie's configuration: the default guard rather
  than a hard-coded `web`, the configured Role and Permission models, and the
  configured pivot table.
- `permissions:refresh` is no longer thrown by a stale permission cache, and
  clears that cache if a sync fails.
- `permissions:refresh` creates a role declared with no permissions, as documented.
- Permission names keep the word "Policy" when it's part of the model's name, so
  `InsurancePolicyPolicy` gives `insurance-policy:*` rather than `insurance:*`,
  which `before()` never matched.
- Helpers no longer treat `__construct`, other magic methods or static methods as
  abilities.
- `before()` checks every role. It stopped at the first role with no matching
  permission row, so a user with roles on two guards could be wrongly denied.

### Added
- A Pest test suite, including tests against real Filament resources, with CI
  across PHP 8.4 and 8.5 and Laravel 12 and 13, on a weekly schedule so that
  upstream releases are checked too.
- Pint and Larastan configuration.
- `permissions:refresh` reports what it synced.
- `UPGRADE.md`.

## [2.0.0] - 2026-08-03

### Changed
- **BREAKING**: Requires `spatie/laravel-permission` ^8.3 (was ^6.21). Consuming
  apps that pin their own `spatie/laravel-permission` constraint must bump it to
  ^8.0 in the same Composer run.
- **BREAKING**: Requires PHP ^8.4 (was `>=8.1`). The declared floor was already
  wrong — `src/BasePolicy.php` and `src/Commands/RefreshPermissions.php` use
  `new Foo()->bar()` (new without parentheses), which is a parse error before 8.4.
- **BREAKING**: Requires Laravel ^12 (was documented as 10+), inherited from
  spatie/laravel-permission v8.

### Fixed
- `before()` (and `after()`, if a policy defines one) are no longer emitted as
  permissions. `Permissions::permissionList()` reflects public policy methods,
  and the Gate hooks were leaking through as e.g. `post:before`, which
  `permissions:refresh` then created and granted.
- `make:policy --withPermissions` now works from the command line. The option was
  read with `options()` (plural — returns the whole array, always truthy), so
  `--model` alone silently produced the permission stub; and it was declared
  `VALUE_OPTIONAL`, so passing the bare flag yielded `null` and was treated as
  off. It is now `VALUE_NONE` and read with `option()`.
- The undocumented `-wp` shortcut has been removed. Symfony shortcuts are single
  characters, so `-wp` never parsed — it failed with `The "-w" option does not
  exist.` Use the long-form `--withPermissions`.
- `BasePolicy::before()` now denies instead of throwing when an ability has no
  permission row. A policy method omitted from every role in `rolePermissions()`
  caused `hasPermissionTo()` to raise `PermissionDoesNotExist`, turning a
  forgotten grant into a 500 rather than a denial.

### Notes
- No source changes were required by the upgrade itself. This package touches only
  `Role`, `Permission`, and `PermissionRegistrar::forgetCachedPermissions()`,
  none of which changed signature in a way that affects us. The v7 event and
  command class renames and the v8 contract signature changes are not referenced
  here.

## [1.0.7] - 2024-12-12

### Added
- Initial public release
- Policy scaffolding with automatic permission generation
- Integration with Spatie Laravel Permission
- Artisan command for generating policies
- Claude Code plugin for autonomous permission implementation
