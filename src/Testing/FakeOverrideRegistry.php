<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Testing;

use Simtabi\Laranail\License\Override\BootReport;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\Facades\LicenseOverride;
use Simtabi\Laranail\License\Override\Profile;

/**
 * A no-op {@see OverrideRegistry} that records profiles instead of applying them.
 *
 * Installed by {@see LicenseOverride::fake()}
 * so a consuming app can assert which overrides its providers registered — without
 * rebinding controllers, mutating config, seeding data, or pushing middleware.
 */
final class FakeOverrideRegistry implements OverrideRegistry
{
    /** @var array<string, Profile> */
    private array $profiles = [];

    /** @var list<string> profile names passed to applyProfile()/apply() */
    private array $appliedProfiles = [];

    private BootReport $report;

    public function __construct()
    {
        $this->report = new BootReport;
    }

    public function profile(string $name): Profile
    {
        return $this->profiles[$name] ??= new Profile($this, $name);
    }

    public function profiles(): array
    {
        return $this->profiles;
    }

    public function loadFromConfig(): void {}

    public function applyBindings(): void {}

    public function applyRuntime(): void {}

    public function applyBooted(): void {}

    public function applyProfile(Profile $profile): void
    {
        $this->profiles[$profile->name] = $profile;
        $this->appliedProfiles[] = $profile->name;
    }

    public function blockedRoutes(): array
    {
        $patterns = [];

        foreach ($this->profiles as $profile) {
            if ($profile->enabled) {
                $patterns = [...$patterns, ...$profile->blockedRoutes];
            }
        }

        return array_values(array_unique($patterns));
    }

    public function isEnabled(): bool
    {
        foreach ($this->profiles as $profile) {
            if ($profile->enabled) {
                return true;
            }
        }

        return false;
    }

    public function report(): BootReport
    {
        return $this->report;
    }

    /**
     * Whether a profile with this name was registered.
     */
    public function applied(string $name): bool
    {
        return isset($this->profiles[$name]);
    }

    /**
     * @return list<string>
     */
    public function appliedProfiles(): array
    {
        return array_values(array_unique($this->appliedProfiles));
    }
}
