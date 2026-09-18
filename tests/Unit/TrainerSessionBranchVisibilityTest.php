<?php

namespace Tests\Unit;

use App\Exports\TrainerSessionActiveExport;
use App\Exports\TrainerSessionExpiredExport;
use App\Models\Trainer\TrainerSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainerSessionBranchVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(new User([
            'branch_store_id' => 7,
            'role' => 'ADMIN',
        ]));
    }

    public function test_all_paid_pt_operational_lists_are_limited_to_active_branch(): void
    {
        $queryCallbacks = [
            fn () => TrainerSession::getActivePTList(),
            fn () => TrainerSession::getPendingPTList(),
            fn () => TrainerSession::getPTWaitingList(),
            fn () => TrainerSession::getUnpaidPTList(),
            fn () => TrainerSession::getExpiredPT(),
            fn () => TrainerSession::getPendingPT(10),
            fn () => TrainerSession::getExpiredTrainerSession(10),
            fn () => TrainerSession::history('', '', '2026-09-01', '2026-09-30'),
        ];

        foreach ($queryCallbacks as $queryCallback) {
            $queries = DB::connection()->pretend($queryCallback);
            $sql = strtolower(preg_replace('/\s+/', ' ', $queries[0]['query']));

            $this->assertStringContainsString('branch_store_id = 7', $sql);
        }
    }

    public function test_paid_pt_excel_exports_are_limited_to_active_branch(): void
    {
        foreach ([new TrainerSessionActiveExport(), new TrainerSessionExpiredExport()] as $export) {
            $queries = DB::connection()->pretend(function () use ($export) {
                $export->view();
            });

            $this->assertNotEmpty($queries);
            $this->assertTrue(collect($queries)->contains(function ($query) {
                return str_contains(strtolower($query['query']), 'branch_store_id')
                    && in_array(7, $query['bindings'], true);
            }));
        }
    }
}
