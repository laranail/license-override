# laranail/license-override

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/license-override.svg)](https://packagist.org/packages/laranail/license-override)
[![Tests](https://github.com/laranail/license-override/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/license-override/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/license-override/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/license-override/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> A generic, runtime-extensible engine for neutralizing a third-party license / call-home layer in a self-hosted Laravel product — rebind gate contracts, sink call-home URLs, and block vendor routes via composable per-vendor **profiles**, without editing core or vendor files.

Requires PHP `^8.4.1 || ^8.5` on Laravel `^13`, built on [`laranail/package-tools`](https://github.com/laranail/package-tools). Vendor-agnostic: works for **any** package's license/call-home layer.

## Install

```bash
composer require laranail/license-override
php artisan vendor:publish --tag="license-override-config"   # optional
```

The service provider and the `LicenseOverride` facade are auto-discovered.

## Quick start

Declaratively in `config/license-override.php`, or at runtime from any service provider `boot()`:

```php
use Simtabi\Laranail\License\Override\Facades\LicenseOverride;

LicenseOverride::profile('acme')
    ->rebind(\App\Http\Controllers\LoginController::class, \App\Overrides\OpenLoginController::class)
    ->neutralize(['acme.verify_url', 'acme.update_url'], sink: 'http://127.0.0.1:9/disabled')
    ->setConfig('acme.telemetry_enabled', false)
    ->blockRoutes(['acme/verify', 'acme/update/*'], groups: ['web'])
    ->apply();
```

Everything is composable and mutable at runtime; the block middleware reads the live profile set,
and the manager and profiles are `Macroable`.

## <a name="documentation"></a>Documentation

Hosted at **<https://opensource.simtabi.com/documentation/laranail/license-override/>**.

### Guides
- [Installation](docs/installation.md)
- [Getting started](docs/getting-started.md)
- [Configuration](docs/configuration.md)
- [Architecture](docs/architecture.md)

### Reference
- [`LicenseOverrideManager` / `OverrideRegistry`](docs/tools/manager.md)
- [`Profile`](docs/tools/profiles.md)

## License

MIT — see [LICENSE](LICENSE).
