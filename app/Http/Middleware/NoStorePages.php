<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages and JSON from the app are per user and per session, so the browser must not keep them:
 * otherwise Back / a restored tab can show the member dashboard after signing in as a producer
 * (or another account's page after sign-out) until a hard refresh. Static assets are untouched
 * (served by the web server, and versioned by file hash).
 */
class NoStorePages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $type = (string) $response->headers->get('Content-Type');
        if ($type === '' || str_contains($type, 'text/html') || str_contains($type, 'json')) {
            $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
