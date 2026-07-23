# Changelog

All notable changes to `laranail/license-override` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
