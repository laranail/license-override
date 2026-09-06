<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Console;

use Simtabi\Laranail\Package\Tools\Commands\Command;
use Simtabi\Laranail\License\Override\Contracts\OverrideRegistry;
use Simtabi\Laranail\Package\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * Doctor check for the override engine: lists the active profiles and blocked
 * routes, and reports any levers that failed during boot. Exits non-zero when a
 * lever failed, so CI / deploy health checks can gate on it.
 *
 * Named `laranail::license-override.health`, not `license-override:health`.
 * Artisan's registry is a flat map, so a second package claiming a name does not
 * conflict — it replaces the first without a word, and the loser's command runs
 * somebody else's code under its own name.
 *
 * The `::` needs the trait below. Symfony's `Command::validateName()` rejects
 * the empty segment with `^[^:]++(:[^:]++)*$`; the trait writes the name past
 * that validator, and dispatch still works because Symfony resolves an exact
 * name before it falls back to splitting on `:`.
 */
final class HealthCommand extends Command
{
    use SupportsNamespacedNames;

    protected $signature = 'laranail::license-override.health';

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
