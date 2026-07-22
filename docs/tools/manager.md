# `LicenseOverrideManager` / `OverrideRegistry`

`Simtabi\Laranail\License\Override\LicenseOverrideManager` implements the
`Simtabi\Laranail\License\Override\Contracts\OverrideRegistry` contract, is bound as a singleton,
and is fronted by the `LicenseOverride` facade.

## Contract

| Method | Returns | Description |
|--------|---------|-------------|
| `profile(string $name)` | `Profile` | Get-or-create the named profile. |
| `profiles()` | `array<string,Profile>` | All profiles. |
| `applyBindings()` | `void` | Container rebindings for enabled profiles (register phase). |
| `applyRuntime()` | `void` | Config overrides + block middleware for enabled profiles (boot phase). |
| `applyProfile(Profile)` | `void` | Apply one profile immediately (runtime additions). |
| `blockedRoutes()` | `list<string>` | Union of blocked patterns across enabled profiles. |
| `isEnabled()` | `bool` | Whether any profile is enabled. |

## Extras (concrete)

- `loadFromConfig()` — hydrate profiles from `config('license-override.profiles')`.
- `LicenseOverrideManager::resolve()` — boot-safe accessor (bound singleton or self-bootstrapped).
- `Macroable` — add custom helper methods at runtime.

## Resolution

- `LicenseOverride::…` (facade) → the singleton.
- `app(OverrideRegistry::class)` (DI) → the singleton.
- `LicenseOverrideManager::resolve()` (static) → singleton if bound, else self-bootstrapped.

---

[← Docs index](../../README.md#documentation)
