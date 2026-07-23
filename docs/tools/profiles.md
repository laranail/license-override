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
| `onRegister(callable $hook)` | Run a closure in the register phase (receives the container). |
| `onBooted(callable $hook)` | Run a closure inside `$app->booted()` — after all providers, so it wins regardless of boot order. |
| `fakeHttp(string $host, mixed $response)` | Intercept call-home to `$host`; other hosts pass through to the network. |
| `apply()` | Apply this profile immediately via its registry. |

All mutators return `$this`. `Profile` is `Macroable`.

## Runtime hooks

`onBooted()` is the general-purpose lever for anything a plain rebind/config can't express — seeding
stored state, re-registering a framework closure (e.g. a Fortify view), or `$app->extend()`-ing a
container-bound array. It runs after every provider has booted, so it beats provider-order races.
`onRegister()` is the rarer register-phase counterpart. `fakeHttp()` installs a host-scoped
`Http::fake` whose closure returns `null` (pass-through) for non-matching hosts, so unrelated outbound
HTTP is untouched.

## Notes

- `neutralize()` is intentionally conservative: only keys already present in config are rewritten,
  so a target package that is not installed is never given phantom config. Use `setConfig()` when
  you deliberately want to create/override a key regardless.
- Rebindings only bind abstracts that exist (`class_exists`/`interface_exists`), so a profile that
  targets an absent host class is a safe no-op.
- **`config:cache`-safe:** the closure-based levers (`onRegister`/`onBooted`/`fakeHttp`) are runtime
  state, never serialized into config. Declarative profiles in `config/license-override.php` carry
  only rebind/config/neutralize/block data.
- Each lever is applied **fail-safe** and **once** per profile per phase — a throwing hook is caught,
  logged, and recorded in the boot report (see [Diagnostics & testing](../diagnostics-and-testing.md))
  rather than breaking boot.

---

[← Docs index](../../README.md#documentation)
