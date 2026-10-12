<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\TipTapTransferExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TipTapTransferController extends Controller
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
            'title' => 'Export Data Tip-Tap',
            'content' => 'admin.tip-tap-transfer.index',
            'defaultFrom' => '2026-09-27T23:59:59',
            'defaultUntil' => now()->format('Y-m-d\TH:i:s'),
        ]);
    }

    public function export(Request $request, TipTapTransferExportService $service)
    {
        $validated = $request->validate([
            'from_at' => ['required', 'date'],
            'until_at' => ['required', 'date', 'after:from_at'],
        ], [
            'until_at.after' => 'Tanggal akhir harus setelah tanggal awal.',
        ]);

        $from = Carbon::parse($validated['from_at'], config('app.timezone'));
        $until = Carbon::parse($validated['until_at'], config('app.timezone'));

        if ($until->isFuture()) {
            throw ValidationException::withMessages([
                'until_at' => 'Tanggal akhir tidak boleh melewati waktu sekarang.',
            ]);
        }

        $export = $service->create($from, $until);

        return response()->download($export['path'], $export['name'], [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
