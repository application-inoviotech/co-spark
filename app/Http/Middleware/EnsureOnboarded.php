<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends members who haven't verified their email / paid their first dues back to registration. */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->status === 'suspended', 403, 'This account is inactive. Please contact COSPARK.');
        if ($request->user()?->needsOnboarding()) {
            return redirect()->route('register');
        }

        return $next($request);
    }
}
