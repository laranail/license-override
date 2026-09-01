<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ServiceProvider;

/**
 * The names this package registers into framework-owned registries.
 *
 * They are flat maps keyed by the name, so a second package claiming one does
 * not collide loudly — it silently replaces the first, and the loser's command
 * then runs somebody else's code under its own name.
 *
 * Asserted against the booted application rather than the provider's source:
 * `hasConfigFile('license-override')` passes an **id**, not a key, and
 * package-tools combines it with `->name('laranail/license-override')` to
 * register `laranail.license-override.*`. Grepping the provider would show a
 * bare-looking string and prove nothing either way.
 */
it('registers its command under the laranail::<slug>.<command> shape', function (): void {
    $names = array_keys(app(Kernel::class)->all());

    expect($names)->toContain('laranail::license-override.health')
        ->and($names)->not->toContain('license-override:health')
        ->and($names)->not->toContain('health');
});

it('claims no generic short alias for it', function (): void {
    // An alias of `license-override:health` would hand back exactly the
    // collision the namespaced name exists to avoid.
    $command = app(Kernel::class)->all()['laranail::license-override.health'] ?? null;

    expect($command)->not->toBeNull()
        ->and($command->getAliases())->toBe([]);
});

it('reads its config from the vendor-namespaced key', function (): void {
    expect(config('laranail.license-override'))->not->toBeNull()
        ->and(config('license-override'))->toBeNull();
});

it('publishes under vendor-scoped tags and claims no bare one', function (): void {
    $groups = ServiceProvider::publishableGroups();

    expect($groups)->not->toContain('license-override')
        ->and($groups)->not->toContain('license-override-config')
        ->and($groups)->not->toContain('config');

    $ours = array_values(array_filter(
        $groups,
        static fn (string $group): bool => str_contains($group, 'license-override'),
    ));

    expect($ours)->not->toBeEmpty();

    foreach ($ours as $group) {
        expect($group)->toStartWith('laranail::license-override');
    }
});
