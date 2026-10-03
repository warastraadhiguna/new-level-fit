<?php

namespace Tests\Feature;

use App\Models\Member\MemberRegistration;
use App\Models\Member\MemberRegistrationPayment;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberInstallmentRegistrationTest extends TestCase
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

        $this->createTables();
        $this->createInstallmentPackage();
        Carbon::setTestNow(Carbon::parse('2026-10-03 10:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_past_start_date_is_not_cancelled_before_initial_payment_is_created(): void
    {
        $registration = $this->createRegistration();

        $this->assertSame('pending', $registration->fresh()->installment_status);
        $this->assertNull($registration->fresh()->installment_cancelled_at);
        $this->assertSame(12, $registration->installments()->count());

        MemberRegistrationPayment::create([
            'branch_store_id' => 1,
            'member_registration_id' => $registration->id,
            'value' => 478000,
            'received_amount' => 478000,
        ]);

        $registration->refresh();

        $this->assertSame('active', $registration->installment_status);
        $this->assertNull($registration->installment_cancelled_at);
        $this->assertDatabaseHas('member_registration_installments', [
            'member_registration_id' => $registration->id,
            'month_number' => 1,
            'paid_amount' => 229000,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('member_registration_installments', [
            'member_registration_id' => $registration->id,
            'month_number' => 12,
            'type' => 'deposit',
            'paid_amount' => 229000,
            'status' => 'paid',
        ]);
    }

    public function test_deleting_initial_payment_still_applies_contract_cancellation_rule(): void
    {
        $registration = $this->createRegistration();
        $payment = MemberRegistrationPayment::create([
            'branch_store_id' => 1,
            'member_registration_id' => $registration->id,
            'value' => 478000,
            'received_amount' => 478000,
        ]);

        $payment->delete();
        $registration->refresh();

        $this->assertSame('cancelled', $registration->installment_status);
        $this->assertNotNull($registration->installment_cancelled_at);
        $this->assertDatabaseHas('member_registration_installments', [
            'member_registration_id' => $registration->id,
            'month_number' => 12,
            'type' => 'deposit',
            'status' => 'forfeited',
        ]);
    }

    private function createRegistration(): MemberRegistration
    {
        return MemberRegistration::create([
            'member_id' => 166,
            'member_package_id' => 37,
            'package_price' => 2748000,
            'admin_price' => 20000,
            'discount_amount' => 0,
            'start_date' => '2026-09-02',
            'days' => 360,
        ]);
    }

    private function createInstallmentPackage(): void
    {
        DB::table('branch_stores')->insert([
            'id' => 1,
            'name' => 'Fitbull',
            'member_installment_enabled' => true,
            'member_installment_grace_days' => 7,
            'member_installment_cancel_days' => 30,
            'member_discount_enabled' => false,
        ]);

        DB::table('member_packages')->insert([
            'id' => 37,
            'branch_store_id' => 1,
            'package_name' => 'FOUNDER BRONZE KONTRAK 12 BULAN ANIN',
            'days' => 360,
            'package_price' => 2748000,
            'admin_price' => 20000,
            'is_installment_plan' => true,
            'installment_monthly_amount' => 229000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::create('branch_stores', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('member_installment_enabled')->default(false);
            $table->integer('member_installment_grace_days')->default(0);
            $table->integer('member_installment_cancel_days')->default(0);
            $table->boolean('member_discount_enabled')->default(false);
        });

        Schema::create('member_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('branch_store_id');
            $table->string('package_name');
            $table->integer('days');
            $table->integer('package_price');
            $table->integer('admin_price');
            $table->boolean('is_installment_plan')->default(false);
            $table->integer('installment_monthly_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('member_registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id');
            $table->integer('member_package_id');
            $table->integer('package_price');
            $table->integer('admin_price');
            $table->integer('discount_amount')->default(0);
            $table->date('start_date');
            $table->integer('days');
            $table->boolean('is_installment_plan')->default(false);
            $table->integer('installment_monthly_amount')->nullable();
            $table->string('installment_status')->nullable();
            $table->string('installment_deposit_status')->nullable();
            $table->integer('installment_grace_days')->nullable();
            $table->integer('installment_cancel_days')->nullable();
            $table->dateTime('installment_cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('member_registration_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_registration_id');
            $table->integer('month_number');
            $table->integer('payment_order');
            $table->string('type');
            $table->date('due_date');
            $table->integer('amount');
            $table->integer('paid_amount')->default(0);
            $table->string('status');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('member_registration_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('branch_store_id')->nullable();
            $table->integer('member_registration_id');
            $table->string('note')->nullable();
            $table->integer('method_payment_id')->nullable();
            $table->integer('value');
            $table->integer('received_amount')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }
}
