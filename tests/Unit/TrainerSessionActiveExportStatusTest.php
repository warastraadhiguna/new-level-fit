<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainerSessionActiveExportStatusTest extends TestCase
{
    public function test_active_export_uses_current_pt_check_in_state_for_status(): void
    {
        $base = [
            'member_phone' => '08123456789',
            'start_date' => '2026-10-01',
            'expired_date' => '2026-11-01',
            'package_name' => 'PT Package',
            'ts_package_price' => 100000,
            'ts_number_of_session' => 10,
            'remaining_sessions' => 9,
            'method_payment_name' => 'Cash',
            'trainer_name' => 'Coach',
            'fc_name' => 'FC',
            'staff_name' => 'Admin',
            'expired_date_status' => 'Running',
            'description' => null,
            'leave_day_status' => 'No Leave Days',
        ];

        $trainerSessions = collect([
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
            (object) array_merge($base, [
                'member_name' => 'Sedang Freeze',
                'leave_day_status' => 'Freeze',
                'check_in_time' => null,
                'check_out_time' => null,
            ]),
        ]);

        $html = (string) view('admin.trainer-session.excel', compact('trainerSessions'))->render();

        $this->assertSame(2, preg_match_all('/<td>\s*Not Start\s*<\/td>/', $html));
        $this->assertSame(1, preg_match_all('/<td>\s*Running\s*<\/td>/', $html));
        $this->assertSame(1, preg_match_all('/<td>\s*Freeze\s*<\/td>/', $html));
    }
}
