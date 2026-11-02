<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin portal access (SOW: role-based access — administrative functions for authorised staff only). */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin() && $request->user()->status === 'active', 403);

        return $next($request);
    }
}
