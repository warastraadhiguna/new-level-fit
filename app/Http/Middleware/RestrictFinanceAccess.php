<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RestrictFinanceAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isFinance()) return $next($request);
        $route=optional($request->route())->getName();
        $access=app(\App\Support\RoleFeatureAccess::class);
        if ($access->hasExplicitRouteGrant($request->user(),$request->route())) return $next($request);
        if ($route==='dashboard' && $request->isMethod('GET')) return redirect()->route('finance.home');
        $feature=$access->featureForRoute($request->route());
        if (in_array($feature,['employees','enrollment','revenue','tip_tap_transfer','attendance_shifts','attendance_schedules','attendance_leaves','attendance_reports'],true) && $access->allows($request->user(),$feature)) return $next($request);
        abort_unless(in_array($route,['finance.home','revenue-report.index','login','logout'],true),403,
            'Akun Finance hanya memiliki akses baca laporan Revenue cabang login.');
        return $next($request);
    }
}
