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
        if ($route==='dashboard' && $request->isMethod('GET')) return redirect()->route('finance.home');
        abort_unless(in_array($route,['finance.home','revenue-report.index','login','logout'],true),403,
            'Akun Finance hanya memiliki akses baca laporan Revenue cabang login.');
        return $next($request);
    }
}
