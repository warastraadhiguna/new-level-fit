<?php
namespace Tests\Feature;
use App\Models\{BranchStore,User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class FaceEnrollmentSettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('branch_stores',function(Blueprint $t) {
            $t->id();$t->timestamps();
            foreach(['name','slug','address','city','phone','email','type','admin_logo','dashboard_finance_visible_roles','revenue_report_visible_roles'] as $name) $t->text($name)->nullable();
            foreach(['is_payment_strict','member_installment_enabled','member_discount_enabled','member_approval_enabled','trainer_discount_enabled','trainer_approval_enabled','pt_free_enabled','pos_inventory_enabled','show_on_gym_landing_page','tip_tap_transfer_enabled'] as $name) $t->boolean($name)->default(false);
            foreach(['member_installment_reminder_days','member_installment_grace_days','member_installment_cancel_days','class_booking_advance_days'] as $name) $t->integer($name)->default(0);
        });
        DB::table('branch_stores')->insert(['id'=>1,'name'=>'Existing Branch']);
        require_once database_path('migrations/2026_10_15_000003_add_face_enrollment_enabled_to_branch_stores.php');
        (new \AddFaceEnrollmentEnabledToBranchStores)->up();
        $owner=new User(['role'=>'OWNER','full_name'=>'Owner','branch_store_id'=>1]);$owner->id=1;
        $owner->setRelation('branchStore',BranchStore::find(1));$this->actingAs($owner);
    }
    private function payload(): array
    {
        return ['name'=>'New Branch','slug'=>'new-branch','address'=>'Address','city'=>'City','phone'=>'08123456789','email'=>'branch@example.com','type'=>'both',
            'is_payment_strict'=>0,'member_installment_enabled'=>0,'member_installment_reminder_days'=>0,'member_installment_grace_days'=>0,'member_installment_cancel_days'=>1,
            'member_discount_enabled'=>0,'member_approval_enabled'=>0,'trainer_discount_enabled'=>0,'trainer_approval_enabled'=>0,'pt_free_enabled'=>0,'pos_inventory_enabled'=>0,
            'show_on_gym_landing_page'=>0,'tip_tap_transfer_enabled'=>0,'class_booking_advance_days'=>1,'dashboard_finance_visible_roles'=>['ALL'],'revenue_report_visible_roles'=>['ALL']];
    }
    public function test_migration_defaults_existing_and_new_branches_to_disabled(): void
    {
        $this->assertFalse(BranchStore::find(1)->face_enrollment_enabled);
        DB::table('branch_stores')->insert(['name'=>'Another Branch']);
        $this->assertEquals(0,DB::table('branch_stores')->where('face_enrollment_enabled',true)->count());
    }
    public function test_secret_branch_form_defaults_to_disabled(): void
    {
        $html=(string) $this->view('admin.branch-store.partials.form-fields');
        $document=new \DOMDocument();@$document->loadHTML($html);
        $selected=(new \DOMXPath($document))->query('//select[@name="face_enrollment_enabled"]/option[@selected]');
        $this->assertCount(1,$selected);$this->assertEquals('0',$selected->item(0)->getAttribute('value'));
    }
    public function test_secret_branch_setting_can_be_saved_and_disabled_again(): void
    {
        $this->post(route('secret-branch-store.store'),$this->payload())->assertRedirect(route('secret-branch-store.index'))->assertSessionHasNoErrors();
        $branch=BranchStore::where('slug','new-branch')->firstOrFail();$this->assertFalse($branch->face_enrollment_enabled);
        $this->put(route('secret-branch-store.update',$branch),$this->payload()+['face_enrollment_enabled'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($branch->fresh()->face_enrollment_enabled);
        $this->put(route('secret-branch-store.update',$branch),$this->payload()+['face_enrollment_enabled'=>0])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($branch->fresh()->face_enrollment_enabled);
    }
    public function test_invalid_setting_is_not_saved(): void
    {
        $this->postJson(route('secret-branch-store.store'),$this->payload()+['face_enrollment_enabled'=>'invalid'])->assertStatus(422)->assertJsonValidationErrors('face_enrollment_enabled');
        $this->assertDatabaseCount('branch_stores',1);
    }
}
