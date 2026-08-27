<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override;

use Illuminate\Support\Traits\Macroable;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;

/**
 * A composable, fluent description of how to neutralize one vendor's license /
 * call-home layer. Vendor-agnostic: nothing here mentions a specific package.
 */
final class Profile
{
    use Macroable;

    public const string DEFAULT_SINK = 'http://127.0.0.1:9/disabled';

    /**
     * @param  array<class-string, class-string>  $rebindings  abstract => concrete
     * @param  array<string, mixed>  $configOverrides  config key => value (set unconditionally)
     * @param  array<string, mixed>  $neutralizations  config key => sink value (set only if the key already exists)
     * @param  list<string>  $blockedRoutes  Request::is() patterns to 404
     * @param  list<string>  $middlewareGroups  route groups to guard
     */
    public function __construct(
        private readonly OverrideRegistry $registry,
        public readonly string $name,
        public bool $enabled = true,
        public array $rebindings = [],
        public array $configOverrides = [],
        public array $neutralizations = [],
        public array $blockedRoutes = [],
        public array $middlewareGroups = ['web'],
    ) {}

    /**
     * Closures run in the register phase (via {@see OverrideRegistry::applyBindings()}).
     * Each receives the container. Rare — most work belongs in {@see onBooted()}.
     *
     * @var list<callable>
     */
    public array $registerHooks = [];

    /**
     * Closures run inside $app->booted() — after every provider has booted, so
     * they are order-independent (e.g. re-registering a Fortify view, seeding
     * stored state, extending a container-bound array). Each receives the container.
     *
     * @var list<callable>
     */
    public array $bootedHooks = [];

    /**
     * Opt-in call-home interception: host substring => response (a value passed to
     * Http::response(), or a Closure(Request): mixed). Non-matching hosts pass
     * through to the network (the fake closure returns null).
     *
     * @var array<string, mixed>
     */
    public array $httpFakes = [];

    public function enable(bool $on = true): static
    {
        $this->enabled = $on;

        return $this;
    }

    public function disable(): static
    {
        return $this->enable(false);
    }

    /**
     * Rebind a container abstract (e.g. a gate controller) to a no-op concrete.
     *
     * @param  class-string  $abstract
     * @param  class-string  $concrete
     */
    public function rebind(string $abstract, string $concrete): static
    {
        $this->rebindings[$abstract] = $concrete;

        return $this;
    }

    /**
     * Set an arbitrary config key unconditionally.
     */
    public function setConfig(string $key, mixed $value): static
    {
        $this->configOverrides[$key] = $value;

        return $this;
    }

    /**
     * Point call-home config URLs at a dead sink — but only keys that already
     * exist, so a target package that is absent is never polluted.
     *
     * @param  list<string>  $keys
     */
    public function neutralize(array $keys, string $sink = self::DEFAULT_SINK): static
    {
        foreach ($keys as $key) {
            $this->neutralizations[$key] = $sink;
        }

        return $this;
    }

    /**
     * Block vendor route patterns (returned as 404).
     *
     * @param  list<string>  $patterns
     * @param  list<string>|null  $groups  route groups to guard (merged; default keeps existing)
     */
    public function blockRoutes(array $patterns, ?array $groups = null): static
    {
        $this->blockedRoutes = array_values(array_unique([...$this->blockedRoutes, ...$patterns]));

        if ($groups !== null) {
            $this->middlewareGroups = array_values(array_unique([...$this->middlewareGroups, ...$groups]));
        }

        return $this;
    }

    /**
     * Register a closure to run in the register phase (receives the container).
     */
    public function onRegister(callable $hook): static
    {
        $this->registerHooks[] = $hook;

        return $this;
    }

    /**
     * Register a closure to run inside $app->booted() — after all providers have
     * booted, so it wins regardless of provider order (receives the container).
     */
    public function onBooted(callable $hook): static
    {
        $this->bootedHooks[] = $hook;

        return $this;
    }

    /**
     * Intercept call-home to a host: any outgoing HTTP whose URL contains $host is
     * answered with $response (a value for Http::response(), or a Closure(Request)).
     * Everything else passes through to the real network. Opt-in.
     */
    public function fakeHttp(string $host, mixed $response): static
    {
        $this->httpFakes[$host] = $response;

        return $this;
    }

    /**
     * Apply this profile immediately (useful when built at runtime after boot).
     */
    public function apply(): static
    {
        $this->registry->applyProfile($this);

        return $this;
    }
}
