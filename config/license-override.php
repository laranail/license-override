<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Declarative override profiles
    |--------------------------------------------------------------------------
    | Each profile neutralizes one vendor's license / call-home layer. Profiles
    | may also be registered/mutated at runtime via the LicenseOverride facade
    | or the OverrideRegistry contract. Example (commented):
    |
    | 'profiles' => [
    |     'acme' => [
    |         'enabled' => env('ACME_OVERRIDE_ENABLED', true),
    |         // Container rebindings: abstract => no-op concrete.
    |         'rebind' => [
    |             \App\Http\Controllers\LoginController::class => \App\Overrides\OpenLoginController::class,
    |         ],
    |         // Sink call-home URLs (only keys that already exist are rewritten).
    |         'neutralize' => [
    |             'sink' => 'http://127.0.0.1:9/disabled',
    |             'keys' => ['acme.verify_url', 'acme.update_url'],
    |         ],
    |         // Arbitrary config overrides (set unconditionally).
    |         'config' => [
    |             'acme.telemetry_enabled' => false,
    |         ],
    |         // Route patterns to 404, and the groups to guard.
    |         'block_routes' => ['acme/verify', 'acme/update/*'],
    |         'middleware_groups' => ['web'],
    |     ],
    | ],
    */
    'profiles' => [],

];
