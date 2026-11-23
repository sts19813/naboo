<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAirbnbIntegrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('features.airbnb_integration_enabled'), 404);

        return $next($request);
    }
}
