<?php

namespace App\Exports;

use App\Services\RevenueReportService;
use App\Models\MethodPayment;
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
    private $methodPaymentId;

    public function __construct(
        int $branchStoreId,
        string $fromDate,
        string $toDate,
        bool $includePos,
        ?string $search = null,
        ?string $category = null,
        ?int $methodPaymentId = null
    ) {
        $this->branchStoreId = $branchStoreId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->includePos = $includePos;
        $this->search = $search;
        $this->category = $category;
        $this->methodPaymentId = $methodPaymentId;
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
                $this->category,
                $this->methodPaymentId
            )
            ->orderByDesc('transaction_at')
            ->orderByDesc('row_id')
            ->get();

        return view('admin.revenue-report.excel', [
            'transactions' => $transactions,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'paymentMethodName' => $this->methodPaymentId ? optional(MethodPayment::find($this->methodPaymentId))->name : 'All Payment Methods',
        ]);
    }
}
