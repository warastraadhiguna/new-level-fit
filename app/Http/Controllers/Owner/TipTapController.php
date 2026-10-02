<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\TipTapService;
use App\Services\TipTapTransferImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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

    public function import(Request $request, TipTapTransferImportService $service)
    {
        $request->validate([
            'transfer_file' => ['required', 'file', 'max:102400'],
            'confirmation' => ['required', 'in:IMPORT DATA'],
        ], [
            'confirmation.in' => 'Konfirmasi harus persis IMPORT DATA.',
        ]);

        try {
            $summary = $service->import($request->file('transfer_file'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return back()->withErrors([
                'transfer_file' => 'Import dibatalkan seluruhnya karena ada konflik ID, kode member, atau relasi data. Database tidak diubah.',
            ]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['transfer_file' => $exception->getMessage()]);
        }

        return back()->with('success', sprintf(
            'Import %s s.d. %s berhasil: %d data baru, %d data diperbarui, %d dilewati, %d referensi baru, dan %d file disimpan.%s',
            $summary['from_exclusive'],
            $summary['until_inclusive'],
            $summary['inserted'],
            $summary['updated'],
            $summary['skipped'],
            $summary['references'],
            $summary['files'],
            $summary['source_missing_files'] > 0
                ? ' ' . $summary['source_missing_files'] . ' file memang tidak tersedia di storage master.'
                : ''
        ));
    }

    public function purgeAll(Request $request, TipTapService $service)
    {
        $request->validate(
            ['confirmation' => ['required', 'in:HAPUS SEMUA PERMANEN']],
            [
                'confirmation.required' => 'Ketik HAPUS SEMUA PERMANEN untuk menghapus seluruh isi tempat sampah.',
                'confirmation.in' => 'Konfirmasi harus persis HAPUS SEMUA PERMANEN.',
            ]
        );
        $count = $service->purgeAll($request->user());
        return redirect()->route('tip-tap.index')
            ->with('success', $count . ' arsip tempat sampah telah dihapus permanen.');
    }

    public function purge(Request $request, TipTapService $service, int $trash)
    {
        $request->validate(['confirmation' => ['required', 'in:HAPUS PERMANEN']]);
        $service->purge($request->user(), $trash);
        return back()->with('success', 'Data dan salinan di tempat sampah telah dihapus permanen.');
    }
}
