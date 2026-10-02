<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\TipTapTransferExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        ]);
    }

    public function export(Request $request, TipTapTransferExportService $service)
    {
        $validated = $request->validate([
            'from_at' => ['required', 'date'],
        ]);

        $from = Carbon::parse($validated['from_at'], config('app.timezone'));
        $until = now();
        abort_if($from->greaterThanOrEqualTo($until), 422, 'Waktu awal harus sebelum waktu export.');

        $export = $service->create($from, $until);

        return response()->download($export['path'], $export['name'], [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
