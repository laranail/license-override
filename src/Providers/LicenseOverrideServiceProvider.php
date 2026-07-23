<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Providers;

use Override;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\LicenseOverrideManager;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Wires the override registry on top of laranail/package-tools.
 *
 * Container rebindings are applied in packageRegistered() (register phase) so a
 * gate rebinding wins before any controller resolves; config overrides + the
 * route-block middleware are applied in packageBooted().
 */
final class LicenseOverrideServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/license-override')
            ->hasConfigFile('license-override')
            ->withoutConfigNamespacing();
    }

    #[Override]
    public function packageRegistered(): void
    {
        // Shared, boot-safe singleton (also used by ::resolve()).
        $manager = LicenseOverrideManager::resolve();
        $this->app->instance(OverrideRegistry::class, $manager);

        $manager->loadFromConfig();
        $manager->applyBindings();
    }

    #[Override]
    public function packageBooted(): void
    {
        $registry = $this->app->make(OverrideRegistry::class);
        $registry->applyRuntime();

        // Booted hooks + HTTP fakes run after every provider has booted, so an
        // override wins regardless of provider order (e.g. re-registering a view
        // a third-party provider also registers). If the app is already booted
        // (runtime registration), the callback fires immediately.
        $this->app->booted(static function () use ($registry): void {
            $registry->applyBooted();
        });
    }
}
