<?php

namespace App\Http\Middleware;

use App\Support\RegistrationSchedule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfferingWindowOpen
{
    /**
     * Stop a Head of Department changing offerings once the administrator's
     * course-offering cutoff has passed. Administrators are never blocked.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        $reason = RegistrationSchedule::offeringClosedReason();

        if ($reason === null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, $reason);
        }

        return back()->withErrors(['offering' => $reason]);
    }
}
