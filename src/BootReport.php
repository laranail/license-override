<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override;

/**
 * A record of what the override engine did while applying profiles: which levers
 * ran, and which failed. Levers are fail-safe (a failure is caught, logged, and
 * recorded here rather than thrown), so this report is how a consumer sees the
 * degraded state — surfaced by the `license-override:health` command.
 */
final class BootReport
{
    /**
     * @param  list<string>  $applied  lever descriptors that ran cleanly (e.g. "froiden.rebind App\\...")
     * @param  list<array{context: string, error: string}>  $failures
     */
    public function __construct(
        private array $applied = [],
        private array $failures = [],
    ) {}

    public function recordApplied(string $lever): void
    {
        $this->applied[] = $lever;
    }

    public function recordFailure(string $context, string $error): void
    {
        $this->failures[] = ['context' => $context, 'error' => $error];
    }

    /**
     * @return list<string>
     */
    public function applied(): array
    {
        return $this->applied;
    }

    /**
     * @return list<array{context: string, error: string}>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    public function hasFailures(): bool
    {
        return $this->failures !== [];
    }

    public function isHealthy(): bool
    {
        return $this->failures === [];
    }
}
