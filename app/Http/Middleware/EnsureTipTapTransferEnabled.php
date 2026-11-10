<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTipTapTransferEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Unauthorized roles receive 403 without revealing the branch setting.
        if (!$user || !app(\App\Support\RoleFeatureAccess::class)->allows($user, 'tip_tap_transfer')) {
            return $next($request);
        }

        abort_unless(
            (bool) optional($user->branchStore)->tip_tap_transfer_enabled,
            404,
            'Fitur Export Tip-Tap tidak aktif untuk cabang ini.'
        );

        return $next($request);
    }
}
