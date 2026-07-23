<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\LicenseOverrideManager;

/**
 * @method static \Simtabi\Laranail\License\Override\Profile profile(string $name)
 * @method static array<string, \Simtabi\Laranail\License\Override\Profile> profiles()
 * @method static void applyBindings()
 * @method static void applyRuntime()
 * @method static void applyBooted()
 * @method static void applyProfile(\Simtabi\Laranail\License\Override\Profile $profile)
 * @method static list<string> blockedRoutes()
 * @method static bool isEnabled()
 *
 * @see LicenseOverrideManager
 */
final class LicenseOverride extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OverrideRegistry::class;
    }
}
