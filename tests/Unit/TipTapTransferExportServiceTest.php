<?php

namespace Tests\Unit;

use App\Services\TipTapTransferExportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class TipTapTransferExportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        foreach (['branch_stores', 'users', 'method_payments', 'member_packages', 'trainer_packages', 'personal_trainers', 'trainer_transaction_types'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->increments('id');
            });
            DB::table($table)->insert(['id' => 1]);
        }

        foreach (['members', 'member_registrations', 'trainer_sessions', 'trainers', 'member_registration_payments',
            'member_registration_installments', 'check_in_members', 'leave_days', 'trainer_session_payments',
            'check_in_trainer_sessions', 'pt_leave_days'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->increments('id');
                if ($table === 'members') {
                    $blueprint->string('photos')->nullable();
                    $blueprint->string('small_photos')->nullable();
                }
                $blueprint->timestamps();
            });
        }
    }

    public function test_export_contains_only_changed_window_and_available_photos(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assets/member/new.jpg', 'photo');
        DB::table('members')->insert([
            ['id' => 1, 'photos' => null, 'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00'],
            ['id' => 2, 'photos' => 'assets/member/new.jpg', 'created_at' => '2026-09-28 10:00:00', 'updated_at' => '2026-09-28 10:00:00'],
            ['id' => 3, 'photos' => null, 'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-29 10:00:00'],
            ['id' => 4, 'photos' => null, 'created_at' => '2026-10-03 10:00:00', 'updated_at' => '2026-10-03 10:00:00'],
        ]);

        $export = app(TipTapTransferExportService::class)->create(
            Carbon::parse('2026-09-27 23:59:59'),
            Carbon::parse('2026-10-02 23:59:59')
        );

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($export['path']) === true);
            $manifest = json_decode($zip->getFromName('manifest.json'), true);
            $dataJson = $zip->getFromName('data.json');
            $data = json_decode($dataJson, true);

            $this->assertSame([2, 3], array_column($data['tables']['members'], 'id'));
            $this->assertCount(1, $data['tables']['branch_stores']);
            $this->assertSame(hash('sha256', $dataJson), $manifest['data_sha256']);
            $this->assertSame('files/assets/member/new.jpg', $manifest['files']['assets/member/new.jpg']);
            $this->assertSame('photo', $zip->getFromName('files/assets/member/new.jpg'));
            $zip->close();
        } finally {
            @unlink($export['path']);
        }
    }
}
