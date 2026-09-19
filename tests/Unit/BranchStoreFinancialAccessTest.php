<?php

namespace Tests\Unit;

use App\Models\BranchStore;
use PHPUnit\Framework\TestCase;

class BranchStoreFinancialAccessTest extends TestCase
{
    public function test_owner_only_setting_rejects_every_non_owner_role(): void
    {
        $branchStore = new BranchStore();
        $branchStore->dashboard_finance_visible_roles = [BranchStore::DASHBOARD_FINANCE_OWNER_ONLY];

        $this->assertTrue($branchStore->canRoleViewDashboardFinance('OWNER'));
        $this->assertFalse($branchStore->canRoleViewDashboardFinance('ADMIN'));
        $this->assertFalse($branchStore->canRoleViewDashboardFinance('CS'));
        $this->assertFalse($branchStore->canRoleViewDashboardFinance('PT'));
    }

    public function test_selected_roles_keep_owner_access_and_only_allow_selected_roles(): void
    {
        $branchStore = new BranchStore();
        $branchStore->dashboard_finance_visible_roles = ['ADMIN', 'FC'];

        $this->assertTrue($branchStore->canRoleViewDashboardFinance('OWNER'));
        $this->assertTrue($branchStore->canRoleViewDashboardFinance('ADMIN'));
        $this->assertTrue($branchStore->canRoleViewDashboardFinance('FC'));
        $this->assertFalse($branchStore->canRoleViewDashboardFinance('CS'));
    }
}
