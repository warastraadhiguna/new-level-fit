<?php

namespace Tests\Unit;

use App\Models\Member\MemberPackage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberPackageAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('member_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('package_name');
            $table->integer('package_price')->default(0);
            $table->integer('admin_price')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_cs_cannot_see_package_when_both_prices_are_zero(): void
    {
        $freeId = $this->insertPackage('Free', 0, 0);
        $paidId = $this->insertPackage('Paid', 100000, 0);
        $adminPaidId = $this->insertPackage('Admin Paid', 0, 25000);
        $cs = new User(['role' => 'CS']);

        $visibleIds = MemberPackage::visibleToUser($cs)->pluck('id')->all();

        $this->assertNotContains($freeId, $visibleIds);
        $this->assertContains($paidId, $visibleIds);
        $this->assertContains($adminPaidId, $visibleIds);
    }

    public function test_non_cs_can_see_free_package(): void
    {
        $freeId = $this->insertPackage('Free', 0, 0);
        $owner = new User(['role' => 'OWNER']);

        $this->assertContains(
            $freeId,
            MemberPackage::visibleToUser($owner)->pluck('id')->all()
        );
    }

    public function test_cs_cannot_assign_free_package_even_with_manual_request(): void
    {
        $package = new MemberPackage([
            'package_name' => 'Free',
            'package_price' => 0,
            'admin_price' => 0,
        ]);

        $this->expectException(ValidationException::class);

        $package->ensureAssignableBy(new User(['role' => 'CS']));
    }

    public function test_non_cs_can_assign_free_package(): void
    {
        $package = new MemberPackage([
            'package_name' => 'Free',
            'package_price' => 0,
            'admin_price' => 0,
        ]);

        $this->assertSame(
            $package,
            $package->ensureAssignableBy(new User(['role' => 'ADMIN']))
        );
    }

    private function insertPackage(string $name, int $packagePrice, int $adminPrice): int
    {
        return DB::table('member_packages')->insertGetId([
            'package_name' => $name,
            'package_price' => $packagePrice,
            'admin_price' => $adminPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
