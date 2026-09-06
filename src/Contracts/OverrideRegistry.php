<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Contracts;

use Simtabi\Laranail\License\Override\Profile;
use Simtabi\Laranail\License\Override\BootReport;

/**
 * Registry of license-override profiles.
 *
 * A profile bundles, for one vendor/target, the container rebindings, config
 * neutralizations/overrides, and route blocks needed to disable that vendor's
 * license/call-home behavior. Profiles may be declared in config or registered
 * (and mutated) at runtime.
 */
interface OverrideRegistry
{
    /**
     * Get (creating if needed) the profile with the given name.
     */
    public function profile(string $name): Profile;

    /**
     * All registered profiles keyed by name.
     *
     * @return array<string, Profile>
     */
    public function profiles(): array;

    /**
     * Hydrate declarative profiles from config('laranail.license-override.profiles').
     */
    public function loadFromConfig(): void;

    /**
     * Apply container rebindings for all enabled profiles (call in register()).
     */
    public function applyBindings(): void;

    /**
     * Apply config overrides and register the route-block middleware for all
     * enabled profiles (call in boot()).
     */
    public function applyRuntime(): void;

    /**
     * Run each enabled profile's booted hooks and install its HTTP fakes. Must be
     * invoked from $app->booted() so it runs after every provider has booted
     * (order-independent).
     */
    public function applyBooted(): void;

    /**
     * Apply a single profile immediately (rebindings + config + middleware).
     * Use when adding a profile at runtime after boot.
     */
    public function applyProfile(Profile $profile): void;

    /**
     * The union of blocked route patterns across all enabled profiles.
     *
     * @return list<string>
     */
    public function blockedRoutes(): array;

    /**
     * Whether any profile is enabled.
     */
    public function isEnabled(): bool;

    /**
     * What the engine did (and any levers that failed) while applying profiles.
     */
    public function report(): BootReport;
}
