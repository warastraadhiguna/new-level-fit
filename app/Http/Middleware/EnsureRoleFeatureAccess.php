<?php
namespace App\Http\Middleware;
use App\Support\RoleFeatureAccess;
use Closure;
use Illuminate\Http\Request;
class EnsureRoleFeatureAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user=$request->user();$access=app(RoleFeatureAccess::class);
        $feature=$access->featureForRoute($request->route());
        if ($user && !$user->isOwner() && $feature) {
            // Role homes stay reachable even when all configurable features are disabled.
            if ($feature==='dashboard' && ($user->isHrd() || $user->isFinance())) return $next($request);
            abort_unless($access->allows($user,$feature),403,'Role Anda tidak memiliki akses ke fitur ini. Hubungi Owner.');
        }
        return $next($request);
    }
}
