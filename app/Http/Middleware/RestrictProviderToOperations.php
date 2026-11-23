<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictProviderToOperations
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['administrador', 'admin']) && $user->hasAnyRole(['proveedor', 'provider'])) {
            abort_unless($request->routeIs('maintenance.index', 'maintenance.show', 'maintenance.status', 'maintenance.costs', 'maintenance.files', 'maintenance.files.destroy', 'maintenance.messages', 'profile.*'), 403);
        }

        return $next($request);
    }
}
