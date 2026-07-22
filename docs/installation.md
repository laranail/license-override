# Installation

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13`

## Install

```bash
composer require laranail/license-override
```

The `LicenseOverrideServiceProvider` and the `LicenseOverride` facade are auto-discovered.

## Publish the config

```bash
php artisan vendor:publish --tag="license-override-config"
```

---

[← Docs index](../README.md#documentation)
