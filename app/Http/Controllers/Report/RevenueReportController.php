<?php

namespace App\Http\Controllers\Report;

use App\Exports\RevenueReportExport;
use App\Http\Controllers\Controller;
use App\Services\RevenueReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RevenueReportController extends Controller
{
    public function index(Request $request, RevenueReportService $report)
    {
        $branchStore = Auth::user()->branchStore;

        abort_unless(
            $branchStore && $branchStore->canRoleViewDashboardFinance(Auth::user()->role),
            403,
            'Role Anda tidak diizinkan melihat Laporan Omzet cabang ini.'
        );

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:150'],
            'view' => ['nullable', 'in:summary,detail'],
            'category' => ['nullable', 'in:membership,pt,pos'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
            'excel' => ['nullable', 'in:1'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = $validated['search'] ?? null;
        $viewMode = $validated['view'] ?? 'summary';
        $category = $validated['category'] ?? null;
        $includePos = (bool) $branchStore->pos_inventory_enabled;

        if ($viewMode === 'summary') {
            $category = null;
            $search = null;
        }

        if (! $includePos && $category === 'pos') {
            $category = null;
        }

        if ($request->input('excel') === '1') {
            return Excel::download(
                new RevenueReportExport(
                    (int) $branchStore->id,
                    $fromDate,
                    $toDate,
                    $includePos,
                    $search,
                    $category
                ),
                'Revenue-Report-' . $fromDate . '-to-' . $toDate . '.xlsx'
            );
        }

        $baseQuery = $report->query(
            (int) $branchStore->id,
            $fromDate,
            $toDate,
            $includePos,
            $search,
            $category
        );

        $summary = (clone $baseQuery)
            ->select('category')
            ->selectRaw('COUNT(*) AS transaction_count')
            ->selectRaw('SUM(amount) AS total_amount')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $transactions = null;

        if ($viewMode === 'detail') {
            $transactions = (clone $baseQuery)
                ->orderByDesc('transaction_at')
                ->orderByDesc('row_id')
                ->paginate((int) ($validated['per_page'] ?? 20))
                ->appends($request->except('page'));
        }

        return view('admin.layouts.wrapper', [
            'title' => 'Revenue Report',
            'content' => 'admin.revenue-report.index',
            'transactions' => $transactions,
            'summary' => $summary,
            'grandTotal' => (float) $summary->sum('total_amount'),
            'transactionCount' => (int) $summary->sum('transaction_count'),
            'fromDate' => Carbon::parse($fromDate)->toDateString(),
            'toDate' => Carbon::parse($toDate)->toDateString(),
            'search' => $search,
            'viewMode' => $viewMode,
            'category' => $category,
            'perPage' => (int) ($validated['per_page'] ?? 20),
            'includePos' => $includePos,
        ]);
    }
}
