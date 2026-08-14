# Changelog

All notable changes to `laranail/license-override` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

Both names this package registers into a framework-owned registry are now vendor-scoped. Those
registries are flat maps, so a second package claiming a name does not collide loudly — it silently
replaces the first, and the loser's command runs somebody else's code under its own name.

| Was | Now |
|---|---|
| `license-override:health` | `laranail::license-override.health` |
| `config('license-override.*')` | `config('laranail.license-override.*')` |
| `config/license-override.php` | `config/laranail/license-override.php` |

The command extends `laranail/package-tools`' `Command` base and uses `SupportsNamespacedNames`,
which is what allows the `::` past Symfony's `validateName()`; dispatch still works because Symfony
resolves an exact name before falling back to splitting on `:`. No short alias is registered — a
`license-override:health` alias would hand back exactly the collision the namespaced name exists to
avoid.

The config change is the removal of a `withoutConfigNamespacing()` opt-out. Applications publishing
or reading the old flat key must move to the namespaced one; `vendor:publish
--tag=laranail::license-override-config` writes the new path.

Guarded by `tests/Feature/NamingConventionTest.php`, which reads the console kernel, the config
repository and `ServiceProvider::publishableGroups()` on a booted app rather than the provider's
source — `hasConfigFile('license-override')` passes an id, not a key, so the source proves nothing
either way.

## [1.2.0] - 2026-07-23

### Added
- **Boot report + `license-override:health`** — the engine now records which levers applied and which
  failed (levers stay fail-safe) into a `BootReport`, exposed via `OverrideRegistry::report()` and the
  `license-override:health` doctor command (non-zero exit when a lever failed, for CI/deploy gates).
- **`LicenseOverride::fake()`** test seam — swaps the registry for a recording `FakeOverrideRegistry`
  so a consuming app can assert which overrides its providers registered, applying no side effects.

### Notes
- The design doc's Manager/driver pattern is realized by the two-package split (the presets package's
  `Preset` contract + auto-detection is the driver layer), so the engine keeps its `Profile` DSL rather
  than adding a redundant driver system.

## [1.1.0] - 2026-07-23

### Added
- Runtime-hook levers on `Profile`: `onRegister(callable)` (register phase) and `onBooted(callable)`
  (runs inside `$app->booted()`, so an override wins regardless of provider boot order).
- `fakeHttp(string $host, mixed $response)` — opt-in, host-scoped call-home interception; a
  pass-through closure returns `null` for non-matching hosts so other outbound HTTP is untouched.
- `OverrideRegistry::applyBooted()` — invoked from `$app->booted()` by the service provider.

### Changed
- Applying the registry is now idempotent per profile per phase: `onRegister`/`onBooted` hooks and
  HTTP fakes run exactly once even when the engine provider and a preset provider both apply the
  shared registry. Every lever is wrapped fail-safe (a throwing override is logged, never fatal).
- Closure-based levers are runtime-only and never serialized into config (config:cache-safe).

## [1.0.0] - 2026-07-22

Initial public release.

### Added
- Vendor-agnostic override engine: `OverrideRegistry` contract, `LicenseOverrideManager`,
  fluent `Profile`, `LicenseOverride` facade, and `BlockOverriddenRoutes` middleware.
- Profiles bundle container rebindings, config neutralizations (only-if-exists) / overrides, and
  route blocks; declarable in `config/license-override.php` or registered/mutated at runtime.
- `Macroable` manager + profiles; boot-safe static `LicenseOverrideManager::resolve()`.
- Built on `laranail/package-tools` (`PackageServiceProvider`).
