<?php

namespace Tests\Unit;

use Tests\TestCase;

class CheckInReportMemberNumberTest extends TestCase
{
    public function test_member_check_in_excel_displays_member_number(): void
    {
        $results = collect([(object) [
            'member_code' => 'WSP0671',
            'member_name' => 'Member Satu',
            'branch_store_name' => 'Level Fit',
            'check_in_time' => '2026-10-20 08:00:00',
            'check_out_time' => '2026-10-20 10:00:00',
        ]]);

        $view = $this->view('admin.gym-report.excel.report-member-checkin', compact('results'));

        $view->assertSee('Member Number');
        $view->assertSee('WSP0671');
    }

    public function test_member_pt_check_in_excel_displays_member_number(): void
    {
        $results = collect([(object) [
            'member_code' => 'WSP0671',
            'member_name' => 'Member Satu',
            'package_name' => 'PT Package',
            'branch_store_name' => 'Level Fit',
            'trainer_name' => 'Coach Satu',
            'check_in_time' => '2026-10-20 08:00:00',
            'check_out_time' => '2026-10-20 10:00:00',
        ]]);

        $view = $this->view('admin.gym-report.excel.report-member-pt-checkin', compact('results'));

        $view->assertSee('Member Number');
        $view->assertSee('WSP0671');
    }
}
