<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOwnerForExcelDownloads
{
    private const EXCEL_ROUTES = [
        'trainer-report-excel',
        'member-active-excel',
        'member-expired-excel',
        'memberRegistrationExcel',
        'ptReportExcel',
    ];

    private const EXCEL_ROUTES_AVAILABLE_TO_ALL_ROLES = [
        'report-member-pt-checkin',
    ];

    public function handle(Request $request, Closure $next)
    {
        $routeName = optional($request->route())->getName();
        $isExcelRequest = (string) $request->input('excel') === '1'
            || in_array($routeName, self::EXCEL_ROUTES, true);

        if (
            $isExcelRequest
            && ! in_array($routeName, self::EXCEL_ROUTES_AVAILABLE_TO_ALL_ROLES, true)
        ) {
            abort_unless(
                $request->user() && $request->user()->isOwner(),
                403,
                'Download Excel hanya dapat dilakukan oleh Owner.'
            );
        }

        return $next($request);
    }
}
