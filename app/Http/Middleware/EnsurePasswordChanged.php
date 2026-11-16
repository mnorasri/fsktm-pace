<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Keep an account that still uses the default password out of the rest of
     * the application until it has chosen a new one.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        // The change-password screen and signing out must stay reachable.
        if ($request->routeIs('password.change', 'password.change.update', 'logout')) {
            return $next($request);
        }

        return redirect()->route('password.change')
            ->with('notice', 'Please change your default password before continuing.');
    }
}
