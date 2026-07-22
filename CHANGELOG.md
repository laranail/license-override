# Changelog

All notable changes to `laranail/license-override` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-22

Initial public release.

### Added
- Vendor-agnostic override engine: `OverrideRegistry` contract, `LicenseOverrideManager`,
  fluent `Profile`, `LicenseOverride` facade, and `BlockOverriddenRoutes` middleware.
- Profiles bundle container rebindings, config neutralizations (only-if-exists) / overrides, and
  route blocks; declarable in `config/license-override.php` or registered/mutated at runtime.
- `Macroable` manager + profiles; boot-safe static `LicenseOverrideManager::resolve()`.
- Built on `laranail/package-tools` (`PackageServiceProvider`).
