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
        if ($route==='dashboard' && $request->isMethod('GET')) return redirect()->route('hrd.home');
        abort_unless(in_array($route,[
            'hrd.home','login','logout',
            'employees.index','employees.store','employees.edit','employees.update','employees.destroy',
            'face-enrollment.index','face-enrollment.store','face-enrollment.samples','face-enrollment.samples.destroy',
        ],true),403,'Akun HRD hanya memiliki akses ke pengelolaan pegawai dan pendaftaran wajah.');
        return $next($request);
    }
}
