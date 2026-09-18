<?php

namespace App\Exports;

use App\Services\RevenueReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RevenueReportExport implements FromView
{
    private $branchStoreId;
    private $fromDate;
    private $toDate;
    private $includePos;
    private $search;
    private $category;

    public function __construct(
        int $branchStoreId,
        string $fromDate,
        string $toDate,
        bool $includePos,
        ?string $search = null,
        ?string $category = null
    ) {
        $this->branchStoreId = $branchStoreId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->includePos = $includePos;
        $this->search = $search;
        $this->category = $category;
    }

    public function view(): View
    {
        $transactions = app(RevenueReportService::class)
            ->query(
                $this->branchStoreId,
                $this->fromDate,
                $this->toDate,
                $this->includePos,
                $this->search,
                $this->category
            )
            ->orderByDesc('transaction_at')
            ->orderByDesc('row_id')
            ->get();

        return view('admin.revenue-report.excel', [
            'transactions' => $transactions,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ]);
    }
}
