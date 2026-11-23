<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveIntegrationClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user();
        abort_unless($client instanceof IntegrationClient && $client->is_active, 403);

        return $next($request);
    }
}
