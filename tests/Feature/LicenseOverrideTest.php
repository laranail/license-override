<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\Facades\LicenseOverride;
use Simtabi\Laranail\License\Override\Http\Middleware\BlockOverriddenRoutes;
use Simtabi\Laranail\License\Override\LicenseOverrideManager;

it('binds the registry contract', function (): void {
    expect(app(OverrideRegistry::class))->toBeInstanceOf(LicenseOverrideManager::class);
});

it('rebinds a container abstract via a profile applied at runtime', function (): void {
    interface_exists(GuardTarget::class) or eval('interface GuardTarget {}');
    class_exists(RealGuard::class) or eval('class RealGuard implements GuardTarget {}');
    class_exists(NoopGuard::class) or eval('class NoopGuard implements GuardTarget {}');

    app()->bind(GuardTarget::class, RealGuard::class);

    LicenseOverride::profile('t')->rebind(GuardTarget::class, NoopGuard::class)->apply();

    expect(app(GuardTarget::class))->toBeInstanceOf(NoopGuard::class);
});

it('neutralizes only existing config keys and sets arbitrary overrides', function (): void {
    config(['acme.verify_url' => 'https://acme.test/verify']);

    LicenseOverride::profile('acme')
        ->neutralize(['acme.verify_url', 'acme.absent_url'], sink: 'http://127.0.0.1:9/disabled')
        ->setConfig('acme.telemetry', false)
        ->apply();

    expect(config('acme.verify_url'))->toBe('http://127.0.0.1:9/disabled')
        ->and(config('acme.absent_url'))->toBeNull()
        ->and(config('acme.telemetry'))->toBeFalse();
});

it('registers the block middleware on the configured route groups', function (): void {
    LicenseOverride::profile('acme')->blockRoutes(['acme/verify'], groups: ['web', 'api'])->apply();

    $groups = app('router')->getMiddlewareGroups();

    expect($groups['web'] ?? [])->toContain(BlockOverriddenRoutes::class)
        ->and($groups['api'] ?? [])->toContain(BlockOverriddenRoutes::class);
});

it('the block middleware 404s blocked paths and passes others', function (): void {
    // Testbench does not execute named middleware groups for ad-hoc routes, so
    // apply the middleware class directly to exercise its blocked-pattern logic
    // (group registration is covered by the test above).
    LicenseOverride::profile('acme')->blockRoutes(['acme/verify', 'acme/update/*'])->apply();

    Route::middleware(BlockOverriddenRoutes::class)->get('acme/verify', fn () => 'x');
    Route::middleware(BlockOverriddenRoutes::class)->get('acme/update/now', fn () => 'x');
    Route::middleware(BlockOverriddenRoutes::class)->get('dashboard', fn () => 'ok');

    $this->get('acme/verify')->assertNotFound();
    $this->get('acme/update/now')->assertNotFound();
    $this->get('dashboard')->assertOk()->assertSee('ok');
});

it('loads declarative profiles from config', function (): void {
    config(['some.url' => 'https://x']);
    config(['license-override.profiles.decl' => [
        'enabled' => true,
        'neutralize' => ['sink' => 'http://127.0.0.1:9/disabled', 'keys' => ['some.url']],
        'block_routes' => ['decl/ping'],
    ]]);

    $registry = app(OverrideRegistry::class);
    $registry->loadFromConfig();
    $registry->applyRuntime();

    expect(config('some.url'))->toBe('http://127.0.0.1:9/disabled')
        ->and($registry->blockedRoutes())->toContain('decl/ping');
});
