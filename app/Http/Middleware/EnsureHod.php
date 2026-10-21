<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHod
{
    /**
     * Restrict a route to the Head of Department (or administrator).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! ($user->isHod() || $user->isAdmin())) {
            abort(403, 'This area is restricted to Heads of Department.');
        }

        return $next($request);
    }
}
