<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user() && Auth::user()->isAdmin()) {
            return $next($request);
        }
        if (Auth::user() && app(\App\Support\RoleFeatureAccess::class)->hasExplicitRouteGrant(Auth::user(),$request->route())) return $next($request);

        abort(403);
    }
}
