<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Restrict a route to one or more roles.
     *
     * @param  string  ...$roles  admin|advisor|student
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = in_array($user->role, $roles, true)
            || (in_array('advisor', $roles, true) && $user->isAdvisor());

        if (! $allowed) {
            abort(403, 'You do not have access to this portal.');
        }

        return $next($request);
    }
}
