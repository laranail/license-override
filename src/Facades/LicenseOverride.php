<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\License\Override\BootReport;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\LicenseOverrideManager;
use Simtabi\Laranail\License\Override\Profile;
use Simtabi\Laranail\License\Override\Testing\FakeOverrideRegistry;

/**
 * @method static Profile profile(string $name)
 * @method static array<string, Profile> profiles()
 * @method static void applyBindings()
 * @method static void applyRuntime()
 * @method static void applyBooted()
 * @method static void applyProfile(Profile $profile)
 * @method static list<string> blockedRoutes()
 * @method static bool isEnabled()
 * @method static BootReport report()
 *
 * @see LicenseOverrideManager
 */
final class LicenseOverride extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OverrideRegistry::class;
    }

    /**
     * Swap the registry for a recording fake so a consuming app can assert which
     * overrides its providers registered, without applying any of them.
     */
    public static function fake(): FakeOverrideRegistry
    {
        $fake = new FakeOverrideRegistry;

        self::swap($fake);
        self::getFacadeApplication()->instance(OverrideRegistry::class, $fake);

        return $fake;
    }
}
