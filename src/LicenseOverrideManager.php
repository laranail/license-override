<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Macroable;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\License\Override\Http\Middleware\BlockOverriddenRoutes;
use Throwable;

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

    /** @var array<string, true> profile names whose register hooks have run */
    private array $registered = [];

    /** @var array<string, true> profile names whose booted hooks/fakes have run */
    private array $booted = [];

    private BootReport $report;

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
        private readonly Router $router,
    ) {
        $this->report = new BootReport;
    }

    /**
     * What the engine did (and any levers that failed) while applying profiles.
     */
    public function report(): BootReport
    {
        return $this->report;
    }

    public function profile(string $name): Profile
    {
        return $this->profiles[$name] ??= new Profile($this, $name);
    }

    public function profiles(): array
    {
        return $this->profiles;
    }

    /**
     * Hydrate declarative profiles from config('laranail.license-override.profiles').
     */
    public function loadFromConfig(): void
    {
        /** @var array<string, array<string, mixed>> $declared */
        $declared = (array) $this->config->get('laranail.license-override.profiles', []);

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
            $this->rebind($profile);
            $this->registerOnce($profile);
        }
    }

    public function applyRuntime(): void
    {
        foreach ($this->enabledProfiles() as $profile) {
            $this->applyConfig($profile);
            $this->guard($profile->middlewareGroups);
        }
    }

    public function applyBooted(): void
    {
        $fakes = [];

        foreach ($this->enabledProfiles() as $profile) {
            $fakes = [...$fakes, ...$this->bootOnce($profile)];
        }

        if ($fakes !== []) {
            $this->safely(fn () => $this->installHttpFakes($fakes), 'fakeHttp');
        }
    }

    public function applyProfile(Profile $profile): void
    {
        if (! $profile->enabled) {
            return;
        }

        $this->rebind($profile);
        $this->registerOnce($profile);
        $this->applyConfig($profile);
        $this->guard($profile->middlewareGroups);

        // Built at runtime (after boot): run the booted hooks + fakes now.
        $fakes = $this->bootOnce($profile);

        if ($fakes !== []) {
            $this->safely(fn () => $this->installHttpFakes($fakes), 'fakeHttp');
        }
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

    private function rebind(Profile $profile): void
    {
        foreach ($profile->rebindings as $abstract => $concrete) {
            if (class_exists($abstract) || interface_exists($abstract)) {
                $this->safely(fn () => $this->container->bind($abstract, $concrete), "rebind {$abstract}");
            }
        }
    }

    /**
     * Run a profile's register hooks exactly once (idempotent across the engine
     * provider and any preset provider that also applies the shared registry).
     */
    private function registerOnce(Profile $profile): void
    {
        if (isset($this->registered[$profile->name])) {
            return;
        }

        $this->registered[$profile->name] = true;
        $this->runHooks($profile->registerHooks, $profile->name, 'onRegister');
    }

    /**
     * Run a profile's booted hooks exactly once and return its HTTP fakes (empty
     * on subsequent calls). Idempotent for the same reason as {@see registerOnce()}.
     *
     * @return array<string, mixed>
     */
    private function bootOnce(Profile $profile): array
    {
        if (isset($this->booted[$profile->name])) {
            return [];
        }

        $this->booted[$profile->name] = true;
        $this->runHooks($profile->bootedHooks, $profile->name, 'onBooted');

        return $profile->httpFakes;
    }

    /**
     * @param  list<callable>  $hooks
     */
    private function runHooks(array $hooks, string $profile, string $phase): void
    {
        foreach ($hooks as $i => $hook) {
            $this->safely(fn () => $hook($this->container), "{$profile}.{$phase}[{$i}]");
        }
    }

    /**
     * Install a single pass-through HTTP fake covering every profile's stubs.
     * A request whose URL contains a registered host substring is answered from
     * that stub; any other request returns null from the closure and executes
     * against the real network.
     *
     * @param  array<string, mixed>  $fakes  host substring => response|Closure(Request)
     */
    private function installHttpFakes(array $fakes): void
    {
        Http::fake(function (HttpClientRequest $request) use ($fakes): mixed {
            foreach ($fakes as $host => $response) {
                if (str_contains($request->url(), (string) $host)) {
                    return $response instanceof Closure ? $response($request) : Http::response($response);
                }
            }

            return null; // pass-through: execute the real request
        });
    }

    /**
     * Run a lever, swallowing (and logging) any failure so a broken override can
     * never brick application boot.
     */
    private function safely(callable $fn, string $context): void
    {
        try {
            $fn();
            $this->report->recordApplied($context);
        } catch (Throwable $e) {
            $this->report->recordFailure($context, $e->getMessage());

            if ($this->container->bound('log')) {
                $this->container->make('log')->warning(
                    "[license-override] lever failed ({$context}): {$e->getMessage()}"
                );
            }
        }
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
