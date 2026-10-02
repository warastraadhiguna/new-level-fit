<?php

namespace Tests\Feature;

use App\Services\TipTapTransferImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class TipTapTransferImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('locks', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
        });
        DB::table('locks')->insert(['id' => 1]);
        Schema::create('trashes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kind');
            $table->unsignedInteger('original_id');
        });
        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name');
            $table->timestamps();
        });
        Schema::create('member_registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('member_id');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('id')->on('members');
        });
        Schema::create('check_in_members', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('member_registration_id');
            $table->dateTime('check_in_time');
            $table->timestamps();
            $table->foreign('member_registration_id')->references('id')->on('member_registrations');
        });

        DB::table('members')->insert([
            'id' => 1, 'full_name' => 'Nama lama',
            'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-01 00:00:00',
        ]);
        DB::table('member_registrations')->insert([
            'id' => 10, 'member_id' => 1, 'description' => 'Lama',
            'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-01 00:00:00',
        ]);
        DB::table('trashes')->insert([
            ['id' => 1, 'kind' => 'member', 'original_id' => 2],
            ['id' => 2, 'kind' => 'membership', 'original_id' => 11],
        ]);
    }

    public function test_import_updates_active_data_adds_new_data_and_does_not_resurrect_deleted_roots(): void
    {
        $file = $this->transferFile([
            'members' => [
                ['id' => 1, 'full_name' => 'Nama terbaru', 'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-29 00:00:00'],
                ['id' => 2, 'full_name' => 'Di tempat sampah', 'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-29 00:00:00'],
                ['id' => 3, 'full_name' => 'Sudah dipurge', 'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-29 00:00:00'],
                ['id' => 4, 'full_name' => 'Member baru', 'created_at' => '2026-09-28 00:00:00', 'updated_at' => '2026-09-28 00:00:00'],
            ],
            'member_registrations' => [
                ['id' => 10, 'member_id' => 1, 'description' => 'Terbaru', 'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-29 00:00:00'],
                ['id' => 11, 'member_id' => 1, 'description' => 'Dihapus', 'created_at' => '2026-09-28 00:00:00', 'updated_at' => '2026-09-28 00:00:00'],
                ['id' => 12, 'member_id' => 4, 'description' => 'Baru', 'created_at' => '2026-09-28 00:00:00', 'updated_at' => '2026-09-28 00:00:00'],
                ['id' => 13, 'member_id' => 1, 'description' => 'Lama dipurge', 'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-29 00:00:00'],
            ],
            'check_in_members' => [
                ['id' => 20, 'member_registration_id' => 10, 'check_in_time' => '2026-09-29 10:00:00', 'created_at' => '2026-09-29 10:00:00', 'updated_at' => '2026-09-29 10:00:00'],
                ['id' => 21, 'member_registration_id' => 11, 'check_in_time' => '2026-09-29 10:00:00', 'created_at' => '2026-09-29 10:00:00', 'updated_at' => '2026-09-29 10:00:00'],
            ],
        ]);

        $summary = app(TipTapTransferImportService::class)->import($file);

        $this->assertSame('Nama terbaru', DB::table('members')->where('id', 1)->value('full_name'));
        $this->assertDatabaseHas('members', ['id' => 4, 'full_name' => 'Member baru']);
        $this->assertDatabaseMissing('members', ['id' => 2]);
        $this->assertDatabaseMissing('members', ['id' => 3]);
        $this->assertDatabaseHas('member_registrations', ['id' => 10, 'description' => 'Terbaru']);
        $this->assertDatabaseHas('member_registrations', ['id' => 12]);
        $this->assertDatabaseMissing('member_registrations', ['id' => 11]);
        $this->assertDatabaseMissing('member_registrations', ['id' => 13]);
        $this->assertDatabaseHas('check_in_members', ['id' => 20]);
        $this->assertDatabaseMissing('check_in_members', ['id' => 21]);
        $this->assertGreaterThan(0, $summary['skipped']);

        app(TipTapTransferImportService::class)->import($file);
        $this->assertSame(1, DB::table('members')->where('id', 4)->count());
        $this->assertSame(1, DB::table('member_registrations')->where('id', 12)->count());
        $this->assertSame(1, DB::table('check_in_members')->where('id', 20)->count());
    }

    private function transferFile(array $tables): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tip-tap-test-') . '.zip';
        $dataJson = json_encode(['tables' => $tables], JSON_THROW_ON_ERROR);
        $manifest = [
            'format' => 'level-fit-tip-tap-transfer',
            'version' => 1,
            'timezone' => 'Asia/Jakarta',
            'from_exclusive' => '2026-09-27 23:59:59',
            'until_inclusive' => '2026-10-02 23:59:59',
            'data_sha256' => hash('sha256', $dataJson),
            'files' => [],
            'missing_files' => [],
        ];
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('data.json', $dataJson);
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $zip->close();

        return new UploadedFile($path, 'transfer.zip', 'application/zip', null, true);
    }
}
