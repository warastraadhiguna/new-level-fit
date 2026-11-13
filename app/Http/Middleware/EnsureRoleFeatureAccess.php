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
        if ($user && $feature) {
            // Role homes stay reachable even when all configurable features are disabled.
            if ($feature==='dashboard' && ($user->isHrd() || $user->isFinance())) return $next($request);
            abort_unless($access->allows($user,$feature,$access->actionForRoute($request->route(),$request->method())),403,'Role Anda tidak memiliki izin untuk tindakan ini. Hubungi Owner.');
            abort_unless($access->branchEnabled($user,$feature),404,'Fitur tidak aktif untuk cabang ini.');
        }
        return $next($request);
    }
}
