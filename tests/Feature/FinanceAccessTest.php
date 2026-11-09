<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore};
use App\Services\RevenueReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema,Hash};
use Tests\TestCase;
class FinanceAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('users',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('branch_store_id');$t->string('full_name');$t->string('email')->unique();
            $t->string('gender');$t->string('role');$t->string('password');$t->rememberToken();$t->timestamps();$t->softDeletes();
        });
        Schema::create('branch_stores',function(Blueprint $t) {
            $t->id();$t->string('name');$t->text('revenue_report_visible_roles')->nullable();
            $t->boolean('pos_inventory_enabled')->default(false);$t->boolean('face_enrollment_enabled')->default(false);
        });
        Schema::create('user_application_access',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('user_id');$t->string('application_code');$t->boolean('is_active');$t->timestamps();$t->unique(['user_id','application_code']);
        });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-10 10:00:00','Asia/Jakarta'));
    }
    private function actor(string $role='FINANCE',int $branch=1): User
    {
        $actor=User::create(['role'=>$role,'branch_store_id'=>$branch,'full_name'=>$role.' User','email'=>uniqid().'@example.com','gender'=>'Male','password'=>bcrypt('SecretPass123')]);
        $actor->applicationAccesses()->create(['application_code'=>'management','is_active'=>true]);
        return $actor;
    }
    private function payload(): array
    {
        return ['full_name'=>'New Finance','email'=>'New.Finance@example.com','gender'=>'Female','password'=>'SecretPass123','password_confirmation'=>'SecretPass123'];
    }
    public function test_finance_can_login_and_has_own_home_without_admin_or_hrd_privileges(): void
    {
        $actor=$this->actor();$this->assertTrue($actor->isFinance());$this->assertFalse($actor->isAdmin());$this->assertFalse($actor->isHrd());
        $this->post('/login',['email'=>$actor->email,'password'=>'SecretPass123'])->assertRedirect(route('finance.home'));
        $this->assertAuthenticatedAs($actor);$this->get('/')->assertRedirect(route('finance.home'));
        $this->get('/finance')->assertOk()->assertSee('Laporan Revenue')->assertDontSee('Pendaftaran Wajah')->assertDontSee('Member Active')->assertDontSee('Perangkat Absensi');
        $this->get('/login')->assertRedirect(route('finance.home'));
        $this->post('/logout')->assertRedirect('/');$this->assertGuest();
    }
    public function test_finance_can_read_revenue_summary_and_detail_only_for_login_branch(): void
    {
        Schema::create('finance_test_transactions',function(Blueprint $t) {
            $t->unsignedInteger('branch_store_id');$t->integer('row_id');$t->dateTime('transaction_at');$t->decimal('amount',12,2);
            foreach(['category','reference_number','customer_name','customer_code','item_name','payment_method','staff_name'] as $field) $t->string($field);
        });
        foreach([1,2] as $branch) DB::table('finance_test_transactions')->insert(['branch_store_id'=>$branch,'row_id'=>$branch,
            'transaction_at'=>'2026-11-10 09:00:00','amount'=>$branch*100,'category'=>'Membership','reference_number'=>'REF'.$branch,
            'customer_name'=>'Branch '.$branch.' Payment','customer_code'=>'C'.$branch,'item_name'=>'Package','payment_method'=>'Cash','staff_name'=>'Staff']);
        $this->mock(RevenueReportService::class,function($mock) {
            $mock->shouldReceive('query')->twice()->with(1,'2026-11-01','2026-11-30',false,null,null)
                ->andReturnUsing(function() { return DB::table('finance_test_transactions')->where('branch_store_id',1); });
        });
        $this->actingAs($this->actor())->get('/revenue-report?branch_store_id=2')->assertOk()->assertViewHas('grandTotal',100.0)->assertDontSee('Download Excel');
        $this->get('/revenue-report?view=detail&branch_store_id=2')->assertOk()->assertSee('Branch 1 Payment')->assertDontSee('Branch 2 Payment')->assertDontSee('Download Excel');
    }
    public function test_revenue_access_respects_secret_branch_permissions(): void
    {
        $actor=$this->actor();
        foreach([['OWNER_ONLY'],['ADMIN']] as $roles) {
            DB::table('branch_stores')->where('id',1)->update(['revenue_report_visible_roles'=>json_encode($roles)]);
            $actor->unsetRelation('branchStore');$this->actingAs($actor)->get('/finance')->assertOk()->assertSee('Hubungi Owner')->assertDontSee('Laporan Revenue</a>',false);
            $this->get('/revenue-report')->assertForbidden();
        }
        $branch=new BranchStore(['revenue_report_visible_roles'=>['FINANCE']]);
        $this->assertTrue($branch->canRoleViewRevenueReport('FINANCE'));$this->assertFalse($branch->canRoleViewRevenueReport('HRD'));
    }
    public function test_finance_cannot_modify_transactions_staff_devices_or_access_employee_features(): void
    {
        $this->actingAs($this->actor());
        foreach(['/member','/staff','/employees','/enroll','/secret-branch-store','/attendance-device','/hrd','/hrd-accounts','/finance-accounts','/tip-tap-transfer','/pos'] as $url) $this->get($url)->assertForbidden();
        $this->post('/member-payment',[])->assertForbidden();
        $this->post('/attendance-device/pair',['label'=>'HP'])->assertForbidden();
        $this->put('/administrator-branch-update',['branch_store_id'=>2])->assertForbidden();
        $this->post('/finance-accounts',$this->payload())->assertForbidden();
    }
    public function test_excel_downloads_remain_owner_only(): void
    {
        $this->actingAs($this->actor())->get('/revenue-report?excel=1')->assertForbidden();
    }
    public function test_owner_can_manage_finance_accounts_without_role_or_branch_escalation(): void
    {
        $this->actingAs($this->actor('OWNER'))->get('/finance-accounts')->assertOk();
        $this->post('/finance-accounts',$this->payload()+['role'=>'OWNER','branch_store_id'=>2])->assertRedirect(route('finance-accounts.index'))->assertSessionHasNoErrors();
        $account=User::where('email','new.finance@example.com')->firstOrFail();
        $this->assertEquals('FINANCE',$account->role);$this->assertEquals(1,$account->branch_store_id);$this->assertTrue(Hash::check('SecretPass123',$account->password));
        $this->assertTrue($account->hasApplicationAccess('management'));$this->assertFalse($account->hasApplicationAccess('gym_landing'));
        $password=$account->password;$data=$this->payload();unset($data['password'],$data['password_confirmation']);
        $this->get('/finance-accounts/'.$account->id.'/edit')->assertOk()->assertSee('New Finance');
        $this->put('/finance-accounts/'.$account->id,$data+['role'=>'ADMIN','branch_store_id'=>2])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals($password,$account->fresh()->password);$this->assertEquals('FINANCE',$account->fresh()->role);$this->assertEquals(1,$account->fresh()->branch_store_id);
        $this->delete('/finance-accounts/'.$account->id)->assertRedirect();$this->assertNotNull(User::withTrashed()->find($account->id)->deleted_at);
    }
    public function test_account_management_rejects_other_roles_other_branches_and_non_admins(): void
    {
        $owner=$this->actor('OWNER');$other=$this->actor('FINANCE',2);$hrd=$this->actor('HRD');$admin=$this->actor('ADMIN');
        $this->actingAs($owner);
        foreach([$other,$hrd,$admin] as $target) {
            $this->get('/finance-accounts/'.$target->id.'/edit')->assertNotFound();$this->put('/finance-accounts/'.$target->id,$this->payload())->assertNotFound();$this->delete('/finance-accounts/'.$target->id)->assertNotFound();
        }
        $this->actingAs($admin)->get('/finance-accounts')->assertOk();
        foreach(['HRD','CS','FC','PT'] as $role) $this->actingAs($this->actor($role))->post('/finance-accounts',$this->payload())->assertForbidden();
    }
    public function test_duplicate_email_invalid_password_and_revoked_management_access_are_rejected(): void
    {
        $finance=$this->actor();$this->actingAs($this->actor('OWNER'));
        $this->postJson('/finance-accounts',array_merge($this->payload(),['email'=>$finance->email]))->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/finance-accounts',array_merge($this->payload(),['password_confirmation'=>'different']))->assertStatus(422)->assertJsonValidationErrors('password');
        $finance->applicationAccesses()->update(['is_active'=>false]);
        $this->actingAs($finance)->get('/finance')->assertRedirect(route('login'));$this->assertGuest();
        $this->post('/login',['email'=>$finance->email,'password'=>'SecretPass123'])->assertRedirect(route('login'))->assertSessionHasErrors('email');$this->assertGuest();
    }
}
