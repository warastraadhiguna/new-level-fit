<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\TipTapService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipTapController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user() && $request->user()->isOwner(), 403);
            return $next($request);
        });
    }

    public function index()
    {
        return view('admin.layouts.wrapper', [
            'title' => 'Tip-Tap v1 — Tempat Sampah',
            'content' => 'admin.tip-tap.index',
            'entries' => DB::table('trashes')
                ->select('id', 'kind', 'original_id', 'member_id', 'label', 'deleted_at')
                ->orderByDesc('id')->paginate(20),
        ]);
    }

    public function trash(Request $request, TipTapService $service, int $member, string $kind, int $record)
    {
        abort_unless(in_array($kind, ['membership', 'pt'], true), 404);
        $service->trash($request->user(), $kind, $record, $member);
        return back()->with('success', 'Data dipindahkan ke Tempat Sampah Tip-Tap dan dikeluarkan dari omzet.');
    }

    public function restore(Request $request, TipTapService $service, int $trash)
    {
        try {
            $service->restore($request->user(), $trash);
        } catch (QueryException $exception) {
            // Keep the encrypted archive intact when IDs, unique keys, or FKs conflict.
            return back()->withErrors(['trash' => 'Restore belum berhasil. Pulihkan data induk yang terkait terlebih dahulu dan pastikan kode/kartu member belum digunakan data lain. Data tetap aman di tempat sampah.']);
        }
        return back()->with('success', 'Data beserta history dan omzet berhasil dipulihkan.');
    }

    public function purge(Request $request, TipTapService $service, int $trash)
    {
        $request->validate(['confirmation' => ['required', 'in:HAPUS PERMANEN']]);
        $service->purge($request->user(), $trash);
        return back()->with('success', 'Data dan salinan di tempat sampah telah dihapus permanen.');
    }
}
