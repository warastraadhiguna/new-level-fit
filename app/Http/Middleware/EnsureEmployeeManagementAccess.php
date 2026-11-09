<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureEmployeeManagementAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && ($request->user()->isAdmin() || $request->user()->isHrd()),403);
        return $next($request);
    }
}
