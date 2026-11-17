<?php

namespace Tests\Feature;

use App\Exports\RevenueReportExport;
use App\Http\Controllers\Report\RevenueReportController;
use App\Models\{BranchStore, User};
use App\Services\RevenueReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class RevenueReportPaymentMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', function (...$parts) { return implode('', $parts); });
        $tables = [
            'method_payments' => ['id', 'name'],
            'members' => ['id', 'branch_store_id', 'full_name', 'email', 'member_code'],
            'member_registrations' => ['id', 'member_id', 'member_package_id', 'days'],
            'member_packages' => ['id', 'package_name'],
            'member_registration_payments' => ['id', 'member_registration_id', 'method_payment_id', 'user_id', 'value', 'created_at'],
            'trainer_sessions' => ['id', 'member_id', 'trainer_package_id', 'is_pt_free', 'branch_store_id'],
            'trainer_packages' => ['id', 'package_name', 'status'],
            'trainer_session_payments' => ['id', 'trainer_session_id', 'method_payment_id', 'user_id', 'value', 'created_at'],
            'users' => ['id', 'full_name'],
            'pos_sales' => ['id', 'branch_store_id', 'status', 'created_at', 'sale_number', 'customer_name', 'cashier_id', 'grand_total'],
            'pos_sale_payments' => ['sale_id', 'method_payment_id'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function ($table) use ($columns) {
                foreach ($columns as $column) {
                    if (in_array($column, ['id', 'branch_store_id', 'member_id', 'member_package_id', 'days', 'member_registration_id', 'method_payment_id', 'user_id', 'trainer_package_id', 'is_pt_free', 'trainer_session_id', 'sale_id', 'cashier_id'])) {
                        $table->integer($column)->nullable();
                    } elseif (in_array($column, ['value', 'grand_total'])) {
                        $table->decimal($column, 12, 2);
                    } else {
                        $table->string($column)->nullable();
                    }
                }
            });
        }
        // Identical labels must still be filtered by their distinct payment method IDs.
        DB::table('method_payments')->insert([['id' => 1, 'name' => 'Cash'], ['id' => 2, 'name' => 'Cash']]);
        DB::table('members')->insert(['id' => 1, 'branch_store_id' => 3, 'full_name' => 'Member', 'member_code' => 'C001']);
        DB::table('member_registrations')->insert(['id' => 1, 'member_id' => 1, 'member_package_id' => 1, 'days' => 30]);
        DB::table('member_packages')->insert(['id' => 1, 'package_name' => 'Membership']);
        DB::table('trainer_sessions')->insert(['id' => 1, 'member_id' => 1, 'trainer_package_id' => 1, 'is_pt_free' => 0, 'branch_store_id' => 3]);
        DB::table('trainer_packages')->insert(['id' => 1, 'package_name' => 'PT Package', 'status' => 'PT']);
        foreach ([1, 2] as $id) {
            $payment = ['id' => $id, 'method_payment_id' => $id, 'value' => $id * 100, 'created_at' => '2026-11-15 12:00:00'];
            DB::table('member_registration_payments')->insert($payment + ['member_registration_id' => 1]);
            DB::table('trainer_session_payments')->insert($payment + ['trainer_session_id' => 1]);
            DB::table('pos_sales')->insert(['id' => $id, 'branch_store_id' => 3, 'status' => 'completed', 'created_at' => '2026-11-15 12:00:00', 'sale_number' => 'POS-' . $id, 'grand_total' => $id * 100]);
            DB::table('pos_sale_payments')->insert(['sale_id' => $id, 'method_payment_id' => $id]);
        }
        $branch = new BranchStore(['name' => 'Candisari', 'pos_inventory_enabled' => true]);
        $branch->id = 3;
        $this->actingAs((new User(['role' => 'OWNER', 'branch_store_id' => 3]))->setRelation('branchStore', $branch));
    }

    public function test_shared_query_filters_each_revenue_source_by_id_without_changing_unfiltered_totals(): void
    {
        $service = app(RevenueReportService::class);
        $all = $service->query(3, '2026-11-01', '2026-11-30', true)->get();
        $filtered = $service->query(3, '2026-11-01', '2026-11-30', true, null, null, 2)->get();
        $this->assertCount(6, $all);
        $this->assertEquals(900, $all->sum('amount'));
        $this->assertCount(3, $filtered);
        $this->assertEquals(600, $filtered->sum('amount'));
        $this->assertEqualsCanonicalizing(['membership', 'trainer', 'pos'], $filtered->pluck('source')->all());
        $this->assertSame([2], $filtered->pluck('method_payment_id')->unique()->values()->all());
        $this->assertCount(0, $service->query(4, '2026-11-01', '2026-11-30', true, null, null, 2)->get());
        $this->assertCount(0, $service->query(3, '2026-10-01', '2026-10-31', true, null, null, 2)->get());
        $this->assertCount(2, $service->query(3, '2026-11-01', '2026-11-30', false, null, null, 2)->get());
        $this->assertCount(1, $service->query(3, '2026-11-01', '2026-11-30', true, 'Member', 'pt', 2)->get());
    }

    public function test_summary_detail_and_drilldown_preserve_method_selection_and_filtered_totals(): void
    {
        foreach (['summary', 'detail'] as $mode) {
            $request = Request::create('/revenue-report', 'GET', ['view' => $mode, 'from_date' => '2026-11-01', 'to_date' => '2026-11-30', 'method_payment_id' => '2']);
            $this->app->instance('request', $request);
            $data = app(RevenueReportController::class)->index($request, app(RevenueReportService::class))->getData();
            $this->assertEquals(600, $data['grandTotal']);
            $this->assertSame(3, $data['transactionCount']);
            $this->assertSame(2, $data['methodPaymentId']);
            $html = view('admin.revenue-report.index', $data + ['errors' => new ViewErrorBag()])->render();
            $this->assertStringContainsString('value="2" selected', $html);
            $this->assertStringContainsString('method_payment_id=2', $html);
            if ($mode === 'detail') {
                $this->assertCount(3, $data['transactions']);
            }
        }
    }

    public function test_excel_export_uses_filtered_rows_and_includes_method_label(): void
    {
        $view = (new RevenueReportExport(3, '2026-11-01', '2026-11-30', true, null, null, 2))->view();
        $this->assertCount(3, $view->getData()['transactions']);
        $this->assertEquals(600, $view->getData()['transactions']->sum('amount'));
        $this->assertStringContainsString('Payment Method: Cash', $view->render());
    }
}
