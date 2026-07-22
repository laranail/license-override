# Configuration

`config/license-override.php` holds one key, `profiles` — a map of profile name → spec.

## Profile spec

| Key | Type | Purpose |
|-----|------|---------|
| `enabled` | bool | Whether the profile is applied. |
| `rebind` | `array<class-string,class-string>` | Container `bind()`s (abstract → no-op concrete). Only existing abstracts are bound. |
| `neutralize.keys` | `list<string>` | Config URL keys to rewrite — only if they already exist. |
| `neutralize.sink` | string | Sink value for neutralized keys (default `http://127.0.0.1:9/disabled`). |
| `config` | `array<string,mixed>` | Arbitrary config overrides, set unconditionally. |
| `block_routes` | `list<string>` | `Request::is()` patterns returned as 404. |
| `middleware_groups` | `list<string>` | Route groups guarded by the block middleware (default `['web']`). |

## Example

```php
'profiles' => [
    'acme' => [
        'enabled' => env('ACME_OVERRIDE_ENABLED', true),
        'rebind' => [\App\Http\Controllers\LoginController::class => \App\Overrides\OpenLoginController::class],
        'neutralize' => ['sink' => 'http://127.0.0.1:9/disabled', 'keys' => ['acme.verify_url', 'acme.update_url']],
        'config' => ['acme.telemetry_enabled' => false],
        'block_routes' => ['acme/verify', 'acme/update/*'],
        'middleware_groups' => ['web'],
    ],
],
```

Runtime registration composes with config profiles — same names merge into the same `Profile`.

---

[← Docs index](../README.md#documentation)
