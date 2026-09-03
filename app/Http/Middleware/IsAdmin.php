<?php

namespace App\Http\Middleware;

use App\Support\ApplicationAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (
            $user->isAdmin()
            && !$user->hasApplicationAccess(ApplicationAccess::MANAGEMENT)
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak memiliki akses ke Management.',
            ]);
        }

        if (in_array(strtoupper((string) $user->role), ['OWNER', 'ADMIN', 'CS', 'CSPOS', 'FC'], true)) {
            return $next($request);
        }

        return redirect('/');
    }
}
