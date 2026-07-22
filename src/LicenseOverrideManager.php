<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Traits\Macroable;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\Http\Middleware\BlockOverriddenRoutes;

/**
 * Default {@see OverrideRegistry}. Holds profiles and applies them: container
 * rebindings (register phase), config overrides + route-block middleware (boot
 * phase), and single-profile application for runtime additions.
 *
 * Macroable so consumers can bolt on custom helpers at runtime.
 */
final class LicenseOverrideManager implements OverrideRegistry
{
    use Macroable;

    /** @var array<string, Profile> */
    private array $profiles = [];

    /** @var array<string, true> route groups already given the block middleware */
    private array $guardedGroups = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
        private readonly Router $router,
    ) {}

    public function profile(string $name): Profile
    {
        return $this->profiles[$name] ??= new Profile($this, $name);
    }

    public function profiles(): array
    {
        return $this->profiles;
    }

    /**
     * Hydrate declarative profiles from config('license-override.profiles').
     */
    public function loadFromConfig(): void
    {
        /** @var array<string, array<string, mixed>> $declared */
        $declared = (array) $this->config->get('license-override.profiles', []);

        foreach ($declared as $name => $spec) {
            $profile = $this->profile($name)->enable((bool) ($spec['enabled'] ?? true));

            foreach ((array) ($spec['rebind'] ?? []) as $abstract => $concrete) {
                $profile->rebind((string) $abstract, (string) $concrete);
            }

            foreach ((array) ($spec['config'] ?? []) as $key => $value) {
                $profile->setConfig((string) $key, $value);
            }

            if (isset($spec['neutralize'])) {
                $neutralize = $spec['neutralize'];
                $keys = (array) ($neutralize['keys'] ?? []);
                $sink = (string) ($neutralize['sink'] ?? Profile::DEFAULT_SINK);
                $profile->neutralize($keys, $sink);
            }

            if (isset($spec['block_routes'])) {
                $profile->blockRoutes(
                    (array) $spec['block_routes'],
                    isset($spec['middleware_groups']) ? (array) $spec['middleware_groups'] : null,
                );
            }
        }
    }

    public function applyBindings(): void
    {
        foreach ($this->enabledProfiles() as $profile) {
            foreach ($profile->rebindings as $abstract => $concrete) {
                if (class_exists($abstract) || interface_exists($abstract)) {
                    $this->container->bind($abstract, $concrete);
                }
            }
        }
    }

    public function applyRuntime(): void
    {
        foreach ($this->enabledProfiles() as $profile) {
            $this->applyConfig($profile);
            $this->guard($profile->middlewareGroups);
        }
    }

    public function applyProfile(Profile $profile): void
    {
        if (! $profile->enabled) {
            return;
        }

        foreach ($profile->rebindings as $abstract => $concrete) {
            if (class_exists($abstract) || interface_exists($abstract)) {
                $this->container->bind($abstract, $concrete);
            }
        }

        $this->applyConfig($profile);
        $this->guard($profile->middlewareGroups);
    }

    public function blockedRoutes(): array
    {
        $patterns = [];

        foreach ($this->enabledProfiles() as $profile) {
            $patterns = [...$patterns, ...$profile->blockedRoutes];
        }

        return array_values(array_unique($patterns));
    }

    public function isEnabled(): bool
    {
        return $this->enabledProfiles() !== [];
    }

    private function applyConfig(Profile $profile): void
    {
        foreach ($profile->configOverrides as $key => $value) {
            $this->config->set($key, $value);
        }

        // Neutralizations only rewrite keys that already exist, so an absent
        // target package is never given bogus config.
        foreach ($profile->neutralizations as $key => $sink) {
            if ($this->config->has($key)) {
                $this->config->set($key, $sink);
            }
        }
    }

    /**
     * @param  list<string>  $groups
     */
    private function guard(array $groups): void
    {
        foreach ($groups as $group) {
            if (! isset($this->guardedGroups[$group])) {
                $this->router->pushMiddlewareToGroup($group, BlockOverriddenRoutes::class);
                $this->guardedGroups[$group] = true;
            }
        }
    }

    /**
     * @return array<string, Profile>
     */
    private function enabledProfiles(): array
    {
        return array_filter($this->profiles, static fn (Profile $p): bool => $p->enabled);
    }

    /**
     * Boot-safe accessor: the bound singleton, or a self-bootstrapped instance
     * stored back into the container. Safe before this package's provider is
     * registered (mirrors laranail/db-guard).
     */
    public static function resolve(): OverrideRegistry
    {
        $container = \Illuminate\Container\Container::getInstance();

        if ($container->bound(OverrideRegistry::class)) {
            $bound = $container->make(OverrideRegistry::class);

            if ($bound instanceof OverrideRegistry) {
                return $bound;
            }
        }

        $manager = new self(
            $container,
            $container->make('config'),
            $container->make('router'),
        );

        $container->instance(OverrideRegistry::class, $manager);

        return $manager;
    }
}
