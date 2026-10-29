<?php

namespace Tests\Unit;

use Tests\TestCase;

class MemberActiveExportStatusTest extends TestCase
{
    public function test_active_export_uses_current_check_in_state_for_membership_status(): void
    {
        $base = [
            'member_code' => 'WSP0001',
            'package_name' => 'Membership',
            'is_all_club' => 1,
            'branch_store_name' => 'Level Fit',
            'start_date' => '2026-10-01',
            'expired_date' => '2026-11-01',
            'mr_package_price' => 100000,
            'mr_admin_price' => 0,
            'mr_discount_amount' => 0,
            'payment_summary' => 100000,
            'leave_day_status' => 'No Leave Days',
        ];

        $memberRegistrations = collect([
            (object) array_merge($base, [
                'member_name' => 'Belum Check In',
                'check_in_time' => null,
                'check_out_time' => null,
            ]),
            (object) array_merge($base, [
                'member_name' => 'Sedang Check In',
                'check_in_time' => '2026-10-20 08:00:00',
                'check_out_time' => null,
            ]),
            (object) array_merge($base, [
                'member_name' => 'Sudah Check Out',
                'check_in_time' => '2026-10-19 08:00:00',
                'check_out_time' => '2026-10-19 10:00:00',
            ]),
        ]);

        $html = (string) view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrations,
            'exportType' => 'active',
        ])->render();

        $this->assertSame(2, preg_match_all('/<td>\s*Not Start\s*<\/td>/', $html));
        $this->assertSame(1, preg_match_all('/<td>\s*Running\s*<\/td>/', $html));
    }
}
