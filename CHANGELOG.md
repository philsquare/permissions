# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
