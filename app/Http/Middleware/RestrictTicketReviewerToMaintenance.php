<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictTicketReviewerToMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole('revisor de tickets')) {
            abort_unless($request->routeIs('maintenance.index', 'maintenance.show', 'maintenance.validate', 'profile.*'), 403);
        }
        return $next($request);
    }
}
