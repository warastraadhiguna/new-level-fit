<?php

namespace Tests\Feature;

use App\Models\{BranchStore, User};
use App\Services\RevenueReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class RevenueReportPdfTest extends TestCase
{
    private $pdf;
    private $renderedText = '';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware();
        $branch = new BranchStore(['name' => 'Level Fit Candisari', 'pos_inventory_enabled' => false]);
        $branch->id = 3;
        $this->actingAs((new User(['role' => 'OWNER', 'branch_store_id' => 3]))->setRelation('branchStore', $branch));
        $this->pdf = app('dompdf.wrapper');
        $this->pdf->getDomPDF()->setCallbacks([[
            'event' => 'begin_page_render',
            'f' => function ($frame) {
                $this->renderedText .= $frame->get_node()->textContent . "\n";
            },
        ]]);
        $this->app->instance('dompdf.wrapper', $this->pdf);
        Schema::create('revenue_fixture', function (Blueprint $table) {
            $table->integer('row_id');
            foreach (['category', 'transaction_at', 'reference_number', 'customer_name', 'customer_code', 'item_name', 'payment_method', 'staff_name'] as $column) {
                $table->string($column);
            }
            $table->decimal('amount', 12, 2);
        });
        for ($i = 1; $i <= 45; $i++) {
            DB::table('revenue_fixture')->insert([
                'row_id' => $i, 'category' => 'Membership', 'transaction_at' => '2026-11-15 12:30:00',
                'reference_number' => 'MEM-' . $i, 'customer_name' => 'Member ' . $i,
                'customer_code' => 'CODE-' . $i, 'item_name' => 'Monthly Membership',
                'payment_method' => 'Cash', 'staff_name' => 'Staff', 'amount' => 100000,
            ]);
        }
    }

    private function expectQuery(?string $search = null, ?string $category = null): void
    {
        $this->mock(RevenueReportService::class)->shouldReceive('query')->once()
            ->with(3, '2026-11-01', '2026-11-30', false, $search, $category)
            ->andReturn(DB::table('revenue_fixture'));
    }

    private function pdfText(): string
    {
        return $this->renderedText;
    }

    public function test_summary_download_has_branch_header_filters_and_correct_totals(): void
    {
        $this->expectQuery();
        $response = $this->get('/revenue-report?pdf=1&view=summary&from_date=2026-11-01&to_date=2026-11-30&category=pt&search=ignored')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('Revenue-Report-Summary-2026-11-01-to-2026-11-30.pdf', $response->headers->get('Content-Disposition'));
        foreach (['Level Fit Candisari', 'Revenue Report - Summary', '01 Nov 2026 - 30 Nov 2026', 'Membership', '4.500.000', '100,0%'] as $text) {
            $this->assertStringContainsString($text, $this->pdfText());
        }
        $this->assertStringNotContainsString('Search Details', $this->pdfText());
        $this->assertStringNotContainsString('POS', $this->pdfText());
        $this->assertSame('portrait', $this->pdf->getDomPDF()->getPaperOrientation());
    }

    public function test_detail_exports_all_filtered_rows_across_pages_and_escapes_search(): void
    {
        $search = '<b>Member</b>';
        $this->expectQuery($search, 'membership');
        $response = $this->get('/revenue-report?' . http_build_query([
            'pdf' => 1, 'view' => 'detail', 'from_date' => '2026-11-01', 'to_date' => '2026-11-30',
            'category' => 'membership', 'search' => $search, 'page' => 2, 'per_page' => 10,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        foreach (['Level Fit Candisari', 'Revenue Report - Details', '01 Nov 2026 - 30 Nov 2026', 'Revenue Type', 'Membership', 'Search Details', $search, 'MEM-1', 'MEM-45', '4.500.000'] as $text) {
            $this->assertStringContainsString($text, $this->pdfText());
        }
        $this->assertSame('landscape', $this->pdf->getDomPDF()->getPaperOrientation());
        $this->assertGreaterThan(1, $this->pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_empty_detail_download_still_generates_a_pdf(): void
    {
        DB::table('revenue_fixture')->delete();
        $this->expectQuery();
        $this->get('/revenue-report?pdf=1&view=detail&from_date=2026-11-01&to_date=2026-11-30')->assertOk();
        $this->assertStringContainsString('No revenue details found', $this->pdfText());
        $this->assertStringContainsString('All Revenue', $this->pdfText());
    }

    public function test_invalid_pdf_filter_returns_validation_error(): void
    {
        $this->getJson('/revenue-report?pdf=1&from_date=2026-11-30&to_date=2026-11-01')
            ->assertStatus(422)->assertJsonValidationErrors('to_date');
    }

    public function test_branch_visibility_gate_protects_pdf_download(): void
    {
        $branch = new BranchStore(['name' => 'Restricted', 'revenue_report_visible_roles' => ['OWNER_ONLY']]);
        $this->actingAs((new User(['role' => 'ADMIN']))->setRelation('branchStore', $branch));
        $this->get('/revenue-report?pdf=1')->assertForbidden();
    }
}
