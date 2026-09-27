<?php

namespace Tests\Unit;

use App\Services\RevenueReportService;
use Tests\TestCase;

class RevenueReportBranchResolutionTest extends TestCase
{
    public function test_membership_uses_member_branch_and_never_staff_or_payment_branch(): void
    {
        $sql = $this->normalizedSql($this->service()->query(
            10,
            '2026-09-01',
            '2026-09-30',
            false
        )->toSql());

        $membershipSql = strstr($sql, 'union all', true);

        $this->assertStringContainsString('`member`.`branch_store_id` = ?', $membershipSql);
        $this->assertStringNotContainsString('payment.branch_store_id', $membershipSql);
        $this->assertStringNotContainsString('staff.branch_store_id', $membershipSql);
    }

    public function test_staff_branch_or_a_later_staff_move_cannot_change_membership_revenue_branch(): void
    {
        $sql = $this->normalizedSql($this->service()->query(
            20,
            '2026-09-01',
            '2026-09-30',
            false
        )->toSql());

        $membershipSql = strstr($sql, 'union all', true);

        $this->assertStringContainsString('`member`.`branch_store_id` = ?', $membershipSql);
        $this->assertSame(1, substr_count($membershipSql, 'branch_store_id'));
    }

    public function test_pt_uses_session_branch_then_member_branch_without_staff_or_payment_fallback(): void
    {
        $sql = $this->normalizedSql($this->service()->query(
            30,
            '2026-09-01',
            '2026-09-30',
            false
        )->toSql());

        $trainerSql = strstr($sql, 'union all');

        $this->assertStringContainsString(
            'coalesce(session.branch_store_id, member.branch_store_id) = ?',
            $trainerSql
        );
        $this->assertStringNotContainsString('payment.branch_store_id', $trainerSql);
        $this->assertStringNotContainsString('staff.branch_store_id', $trainerSql);
    }

    public function test_summary_detail_and_excel_share_the_same_service_query(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Report/RevenueReportController.php'));
        $export = file_get_contents(app_path('Exports/RevenueReportExport.php'));

        $this->assertStringContainsString('$report->query(', $controller);
        $this->assertStringContainsString('app(RevenueReportService::class)', $export);
        $this->assertStringContainsString('->query(', $export);
    }

    private function service(): RevenueReportService
    {
        return app(RevenueReportService::class);
    }

    private function normalizedSql(string $sql): string
    {
        return strtolower(preg_replace('/\s+/', ' ', $sql));
    }
}
