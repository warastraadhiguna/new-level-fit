<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureFaceEnrollmentEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            (bool) optional($request->user()->branchStore)->face_enrollment_enabled,
            404,
            'Pendaftaran wajah belum diaktifkan untuk cabang ini.'
        );
        return $next($request);
    }
}
