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

    public function handle(Request $request, Closure $next)
    {
        $routeName = optional($request->route())->getName();
        $isExcelRequest = (string) $request->input('excel') === '1'
            || in_array($routeName, self::EXCEL_ROUTES, true);

        if ($isExcelRequest) {
            abort_unless(
                $request->user() && $request->user()->isOwner(),
                403,
                'Download Excel hanya dapat dilakukan oleh Owner.'
            );
        }

        return $next($request);
    }
}
