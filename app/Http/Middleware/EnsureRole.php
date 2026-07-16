<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Section-level gate: the route requires one of the given roles, and the user
 * must be active. Row-level authorisation is still the job of Policies — this
 * middleware gates the section, Policies gate the row. Both, always.
 *
 * Usage: ->middleware('role:coordinator')  or  'role:field_supervisor,coordinator'
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_active) {
            abort(403);
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
