<?php

namespace App\Http\Middleware;

use App\Support\ApplicationAccess;
use Closure;
use Illuminate\Http\Request;

class RestrictFinanceAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->isFinance()) return $next($request);
        $name = optional($request->route())->getName();
        if (in_array($name, ['login', 'logout'], true)) return $next($request);
        abort_unless($user->hasApplicationAccess(ApplicationAccess::MANAGEMENT), 403);
        if ($name === 'dashboard' && $request->isMethod('GET')) return redirect()->route('tip-tap.index');

        $reads = ['tip-tap.index', 'members.index', 'member.index', 'members.membership-history',
            'members.pt-history', 'members.membership-check-in-history', 'members.pt-check-in-history'];
        $allowed = ($request->isMethod('GET') && in_array($name, $reads, true))
            || ($request->isMethod('POST') && $name === 'tip-tap.import')
            || ($request->isMethod('DELETE') && in_array($name, ['tip-tap.trash', 'member.destroy', 'members.destroy'], true));
        abort_unless($allowed, 403, 'Finance hanya dapat import dan memindahkan data ke Tempat Sampah Tip-Tap.');
        return $next($request);
    }
}
