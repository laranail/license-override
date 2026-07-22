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

    public const DEFAULT_SINK = 'http://127.0.0.1:9/disabled';

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
     * Apply this profile immediately (useful when built at runtime after boot).
     */
    public function apply(): static
    {
        $this->registry->applyProfile($this);

        return $this;
    }
}
