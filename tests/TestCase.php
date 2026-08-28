<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Tests;

use Illuminate\Foundation\Application;
use Simtabi\Laranail\License\Override\Providers\LicenseOverrideServiceProvider;
use Simtabi\Laranail\Package\Tools\Testing\IsolatedTestCase;

abstract class TestCase extends IsolatedTestCase
{
    /**
     * @param Application $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LicenseOverrideServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
}
