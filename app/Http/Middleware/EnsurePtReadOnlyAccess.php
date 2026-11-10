<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePtReadOnlyAccess
{
    private const ALLOWED_ROUTES = [
        'dashboard',
        'trainer-session.index',
        'trainer-session-pending',
        'trainer-session-unpaid',
        'trainer-session-over.index',
        'trainer-session-waiting-list',
        'pt-free.active',
        'pt-free.pending',
        'pt-free.expired',
        'pt-free.waiting-list',
        'report-member-pt-checkin',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isPt()) {
            return $next($request);
        }

        // Export creates a file without modifying business data; require an explicit grant.
        if (in_array(optional($request->route())->getName(), ['tip-tap-transfer.index','tip-tap-transfer.export'], true)
            && app(\App\Support\RoleFeatureAccess::class)->allows($user, 'tip_tap_transfer')) {
            return $next($request);
        }

        if (
            optional($request->route())->getName() === 'revenue-report.index'
            && optional($user->branchStore)->canRoleViewRevenueReport($user->role)
            && in_array($request->method(), ['GET', 'HEAD'], true)
        ) {
            return $next($request);
        }

        abort_unless(
            in_array($request->method(), ['GET', 'HEAD'], true)
                && in_array(optional($request->route())->getName(), self::ALLOWED_ROUTES, true),
            403,
            'Akun PT hanya memiliki akses lihat.'
        );

        return $next($request);
    }
}
