# `LicenseOverrideManager` / `OverrideRegistry`

`Simtabi\Laranail\License\Override\LicenseOverrideManager` implements the
`Simtabi\Laranail\License\Override\Contracts\OverrideRegistry` contract, is bound as a singleton,
and is fronted by the `LicenseOverride` facade.

## Contract

| Method | Returns | Description |
|--------|---------|-------------|
| `profile(string $name)` | `Profile` | Get-or-create the named profile. |
| `profiles()` | `array<string,Profile>` | All profiles. |
| `applyBindings()` | `void` | Container rebindings + register hooks for enabled profiles (register phase). |
| `applyRuntime()` | `void` | Config overrides + block middleware for enabled profiles (boot phase). |
| `applyBooted()` | `void` | Booted hooks + HTTP fakes; invoke from `$app->booted()` (order-independent). |
| `applyProfile(Profile)` | `void` | Apply one profile immediately (runtime additions). |
| `blockedRoutes()` | `list<string>` | Union of blocked patterns across enabled profiles. |
| `isEnabled()` | `bool` | Whether any profile is enabled. |
| `report()` | `BootReport` | What applied and what failed while applying profiles. |

## Apply phases

The service provider drives the phases: rebindings + `onRegister` hooks in `packageRegistered()`,
config + block middleware in `packageBooted()`, and `applyBooted()` scheduled via `$app->booted()`.
Applying is **idempotent per profile per phase** — hooks and HTTP fakes run exactly once even if the
engine provider and a preset provider both apply the shared registry — and every lever is **fail-safe**
(a failure is caught, logged, and recorded in the boot report, never rethrown).

## Extras (concrete)

- `loadFromConfig()` — hydrate profiles from `config('license-override.profiles')`.
- `LicenseOverrideManager::resolve()` — boot-safe accessor (bound singleton or self-bootstrapped).
- `report()` — the `BootReport` surfaced by the `license-override:health` command.
- `Macroable` — add custom helper methods at runtime.
- Testing: `LicenseOverride::fake()` swaps in a recording registry — see
  [Diagnostics & testing](../diagnostics-and-testing.md).

## Resolution

- `LicenseOverride::…` (facade) → the singleton.
- `app(OverrideRegistry::class)` (DI) → the singleton.
- `LicenseOverrideManager::resolve()` (static) → singleton if bound, else self-bootstrapped.

---

[← Docs index](../../README.md#documentation)
