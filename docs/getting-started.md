# Getting started

Neutralize any vendor's license / call-home layer with a composable profile.

## A profile has four levers

| Lever | Method | Effect |
|-------|--------|--------|
| Rebind a gate | `rebind($abstract, $concrete)` | Swap a container-resolved class (e.g. a login controller) for a no-op. |
| Sink call-home | `neutralize($keys, $sink)` | Rewrite existing config URL keys to a dead sink (absent keys untouched). |
| Override config | `setConfig($key, $value)` | Set any config key unconditionally (e.g. a telemetry flag). |
| Block routes | `blockRoutes($patterns, $groups)` | Return 404 for vendor route patterns. |

## Declarative (config)

```php
// config/license-override.php
'profiles' => [
    'acme' => [
        'enabled' => env('ACME_OVERRIDE_ENABLED', true),
        'rebind' => [\App\Http\Controllers\LoginController::class => \App\Overrides\OpenLoginController::class],
        'neutralize' => ['sink' => 'http://127.0.0.1:9/disabled', 'keys' => ['acme.verify_url']],
        'config' => ['acme.telemetry_enabled' => false],
        'block_routes' => ['acme/verify', 'acme/update/*'],
        'middleware_groups' => ['web'],
    ],
],
```

## Runtime

From any service provider `boot()` (rebindings are best done in `register()`):

```php
use Simtabi\Laranail\License\Override\Facades\LicenseOverride;

LicenseOverride::profile('acme')
    ->neutralize(['acme.verify_url'])
    ->blockRoutes(['acme/verify'])
    ->apply();
```

## Extending

Both `LicenseOverrideManager` and `Profile` are `Macroable`:

```php
LicenseOverride::macro('killTelemetry', fn (string $key) => $this->profile('telemetry')->setConfig($key, false)->apply());
```

---

[← Docs index](../README.md#documentation)
