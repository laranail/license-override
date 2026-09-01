<?php

declare(strict_types=1);

namespace Simtabi\Laranail\License\Override\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\License\Override\LicenseOverrideManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns 404 for any path matching the aggregate blocked-route patterns of the
 * enabled profiles. Reads the patterns live, so profiles added at runtime are
 * honored on the next request.
 */
final class BlockOverriddenRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $patterns = LicenseOverrideManager::resolve()->blockedRoutes();

        if ($patterns !== [] && $request->is(...$patterns)) {
            // 404 is indistinguishable from "route does not exist".
            abort(404);
        }

        return $next($request);
    }
}
