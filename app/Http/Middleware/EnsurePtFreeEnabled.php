<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePtFreeEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless((bool) optional($request->user()->branchStore)->pt_free_enabled, 404);

        return $next($request);
    }
}
