# `Profile`

`Simtabi\Laranail\License\Override\Profile` is the fluent, mutable description of one vendor's
override. Obtain one with `LicenseOverride::profile($name)`; the same name always returns the same
instance, so config and runtime contributions merge.

## Fluent API

| Method | Effect |
|--------|--------|
| `enable(bool $on = true)` / `disable()` | Toggle whether the profile is applied. |
| `rebind($abstract, $concrete)` | Add a container rebinding (abstract → no-op concrete). |
| `neutralize($keys, $sink = self::DEFAULT_SINK)` | Sink existing config URL keys. |
| `setConfig($key, $value)` | Set an arbitrary config key unconditionally. |
| `blockRoutes($patterns, $groups = null)` | Add 404 route patterns (and optionally groups). |
| `apply()` | Apply this profile immediately via its registry. |

All mutators return `$this`. `Profile` is `Macroable`.

## Notes

- `neutralize()` is intentionally conservative: only keys already present in config are rewritten,
  so a target package that is not installed is never given phantom config. Use `setConfig()` when
  you deliberately want to create/override a key regardless.
- Rebindings only bind abstracts that exist (`class_exists`/`interface_exists`), so a profile that
  targets an absent host class is a safe no-op.

---

[← Docs index](../../README.md#documentation)
