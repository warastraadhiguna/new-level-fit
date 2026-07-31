<?php

namespace App\Http\Middleware;

use App\Models\BranchStore;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EnsurePosInventoryEnabled
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (
            !$user
            || !in_array($user->role, ['ADMIN', 'CS', 'CSPOS'], true)
            || !$user->branch_store_id
            || !Schema::hasColumn('branch_stores', 'pos_inventory_enabled')
            || !BranchStore::whereKey($user->branch_store_id)->where('pos_inventory_enabled', true)->exists()
        ) {
            abort(404);
        }

        return $next($request);
    }
}
