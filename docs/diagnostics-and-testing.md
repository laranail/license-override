# Diagnostics & testing

The boot report, the `license-override:health` command, and the `LicenseOverride::fake()` test seam.

## The boot report

Every lever is applied fail-safe: a throwing rebind, hook, or HTTP fake is caught, logged, and
recorded rather than breaking application boot. `OverrideRegistry::report()` returns the `BootReport`
of what happened:

```php
use Simtabi\Laranail\License\Override\Facades\LicenseOverride;

$report = LicenseOverride::report();

$report->applied();      // list<string> — lever descriptors that ran cleanly
$report->failures();     // list<array{context: string, error: string}>
$report->hasFailures();  // bool
$report->isHealthy();    // bool — no failures
```

## `license-override:health`

A doctor command that prints the active profiles, the aggregate blocked routes, and any failed
levers. It **exits non-zero when a lever failed**, so a deploy or CI step can gate on it:

```bash
php artisan license-override:health
```

```
License override — profiles:
  [on ] froiden

Blocked routes: check-env, verify-purchase, admin/update-version/*
Levers applied: 3

All applied levers are healthy.
```

Because the app boots (and therefore applies profiles) before the command runs, the report reflects
the real boot. Wire it into a release pipeline to catch a silently degraded override — e.g. a rebind
target that a vendor update renamed.

## `LicenseOverride::fake()`

For a consuming app's own tests, `fake()` swaps the registry for a recording `FakeOverrideRegistry`
that captures profiles **without applying any side effects** — no container rebinds, config mutation,
seeding, or middleware. Assert what your providers registered:

```php
use Simtabi\Laranail\License\Override\Facades\LicenseOverride;

$fake = LicenseOverride::fake();

// ... boot the code under test (your provider registers a profile) ...

expect($fake->applied('froiden'))->toBeTrue();
expect($fake->appliedProfiles())->toContain('froiden');
expect($fake->blockedRoutes())->toContain('verify-purchase');
```

The fake implements the full `OverrideRegistry` contract, so anything typed against the contract keeps
working; only the side effects are suppressed.

---

[← Docs index](../README.md#documentation)
