# Architecture

How profiles are applied, when, and why it survives updates.

## Registry + profiles

`LicenseOverrideManager` (bound to the `OverrideRegistry` contract) holds named `Profile`
instances. Each profile carries rebindings, config overrides, neutralizations, blocked routes, and
target middleware groups. Profiles come from `config('license-override.profiles')` (declarative) or
from runtime calls to `LicenseOverride::profile(...)`.

## Application phases

- **register()** — `applyBindings()` runs container `bind()`s for enabled profiles, so gate
  rebindings win before any controller resolves. Only binds abstracts that actually exist.
- **boot()** — `applyRuntime()` applies config overrides + neutralizations (the latter only when
  the key already exists, so an absent target package is never polluted) and pushes the
  `BlockOverriddenRoutes` middleware onto each profile's groups (once per group).
- **runtime** — `Profile::apply()` / `applyProfile()` performs all three immediately, for profiles
  added after boot. The block middleware reads `blockedRoutes()` **live** per request, so runtime
  additions take effect on the next request.

## Boot-safety

`LicenseOverrideManager::resolve()` returns the bound singleton or self-bootstraps one from the
container (`config` + `router`) and stores it back — so presets can register profiles at the
earliest boot even before this engine's provider runs (mirrors `laranail/db-guard`). The
`BlockOverriddenRoutes` middleware also resolves through it, so it never depends on registration
order.

## Extensibility

`LicenseOverrideManager` and `Profile` use `Macroable`; the `OverrideRegistry` contract is
rebindable if you need a different registry implementation entirely.

## Built on `laranail/package-tools`

`LicenseOverrideServiceProvider extends Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider`.
`configurePackage()` declares the config file; the container rebindings are applied in
`packageRegistered()` (register phase, so a gate rebinding wins before controllers resolve) and the
config overrides + route-block middleware in `packageBooted()`.

---

[← Docs index](../README.md#documentation)
