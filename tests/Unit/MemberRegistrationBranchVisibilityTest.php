<?php

namespace Tests\Unit;

use App\Models\Member\MemberRegistration;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberRegistrationBranchVisibilityTest extends TestCase
{
    public function test_active_list_allows_current_branch_or_all_club(): void
    {
        $queries = DB::connection()->pretend(function () {
            MemberRegistration::getActiveList('', '', 'no', 7);
        });

        $this->assertBranchOrAllClubCondition($queries[0]['query']);
    }

    public function test_pending_list_allows_current_branch_or_all_club(): void
    {
        $queries = DB::connection()->pretend(function () {
            MemberRegistration::getPendingList('', 7);
        });

        $this->assertBranchOrAllClubCondition($queries[0]['query']);
    }

    public function test_history_allows_current_branch_or_all_club(): void
    {
        $queries = DB::connection()->pretend(function () {
            MemberRegistration::history('', '', '2026-09-01', '2026-09-30', 7);
        });

        $this->assertBranchOrAllClubCondition($queries[0]['query']);
    }

    public function test_expired_list_uses_branch_or_all_club_filter(): void
    {
        $sql = str_replace(['`', '"'], '', MemberRegistration::expiredRegistrations('', 7)->toSql());

        $this->assertStringContainsString('a.branch_store_id = ?', $sql);
        $this->assertStringContainsString('b.is_all_club = ?', $sql);
        $this->assertStringContainsString(' or ', strtolower($sql));
    }

    private function assertBranchOrAllClubCondition(string $sql): void
    {
        $normalizedSql = strtolower(preg_replace('/\s+/', ' ', $sql));

        $this->assertStringContainsString(
            '(mbr.branch_store_id=7 or mbr_pkg.is_all_club=1)',
            $normalizedSql
        );
    }
}
