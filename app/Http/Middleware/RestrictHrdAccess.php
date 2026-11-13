<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RestrictHrdAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isHrd()) return $next($request);
        $route=optional($request->route())->getName();
        $access=app(\App\Support\RoleFeatureAccess::class);
        if ($access->hasExplicitRouteGrant($request->user(),$request->route())) return $next($request);
        if ($route==='dashboard' && $request->isMethod('GET')) return redirect()->route('hrd.home');
        $feature=$access->featureForRoute($request->route());
        if (in_array($feature,['employees','enrollment','revenue','tip_tap_transfer'],true) && $access->allows($request->user(),$feature)) return $next($request);
        abort_unless(in_array($route,[
            'hrd.home','login','logout',
            'employees.index','employees.store','employees.edit','employees.update','employees.destroy',
            'face-enrollment.index','face-enrollment.store','face-enrollment.samples','face-enrollment.samples.destroy',
        ],true),403,'Akun HRD hanya memiliki akses ke pengelolaan pegawai dan pendaftaran wajah.');
        return $next($request);
    }
}
