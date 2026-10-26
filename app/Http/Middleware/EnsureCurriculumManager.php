<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Curriculum management: the administrator, a Head of Department and a Head
 * of Program may all add and maintain programmes, courses and study plans.
 */
class EnsureCurriculumManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! ($user->isAdmin() || $user->isHod() || $user->isHeadOfProgram())) {
            abort(403, 'This area is restricted to curriculum managers.');
        }

        return $next($request);
    }
}
