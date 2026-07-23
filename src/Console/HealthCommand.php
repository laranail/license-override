<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Console;

use Illuminate\Console\Command;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;

/**
 * Doctor check for the override engine: lists the active profiles and blocked
 * routes, and reports any levers that failed during boot. Exits non-zero when a
 * lever failed, so CI / deploy health checks can gate on it.
 */
final class HealthCommand extends Command
{
    protected $signature = 'license-override:health';

    protected $description = 'Report the license-override engine state and any failed levers.';

    public function handle(OverrideRegistry $registry): int
    {
        $profiles = $registry->profiles();
        $report = $registry->report();

        $this->line('License override — profiles:');

        if ($profiles === []) {
            $this->line('  (none registered)');
        }

        foreach ($profiles as $name => $profile) {
            $this->line(sprintf('  [%s] %s', $profile->enabled ? 'on ' : 'off', $name));
        }

        $blocked = $registry->blockedRoutes();
        $this->line(sprintf("\nBlocked routes: %s", $blocked === [] ? '(none)' : implode(', ', $blocked)));
        $this->line(sprintf('Levers applied: %d', count($report->applied())));

        if ($report->hasFailures()) {
            $this->newLine();
            $this->error(sprintf('Failed levers: %d', count($report->failures())));

            foreach ($report->failures() as $failure) {
                $this->line(sprintf('  x %s — %s', $failure['context'], $failure['error']));
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All applied levers are healthy.');

        return self::SUCCESS;
    }
}
