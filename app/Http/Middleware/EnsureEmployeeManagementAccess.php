<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureEmployeeManagementAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && app(\App\Support\RoleFeatureAccess::class)->allowsRoute($request->user(),$request->route()->getName()),403);
        return $next($request);
    }
}
