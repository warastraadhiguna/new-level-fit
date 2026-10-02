<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTipTapTransferEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Authorization remains the controller's responsibility. This keeps
        // non-owners receiving 403 instead of revealing the feature setting.
        if (!$user || !$user->isOwner()) {
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
