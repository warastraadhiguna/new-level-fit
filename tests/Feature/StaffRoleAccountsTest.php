<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class StaffRoleAccountsTest extends TestCase
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
        foreach (['personal_trainers','class_instructors'] as $table) Schema::create($table,function(Blueprint $t) { $t->id();$t->softDeletes(); });
        $this->travelTo(\Carbon\Carbon::parse('2026-11-10 10:00:00','Asia/Jakarta'));
    }
    private function actor(string $role='FINANCE',int $branch=1): User
    {
        $actor=User::create(['role'=>$role,'branch_store_id'=>$branch,'full_name'=>$role.' User','email'=>uniqid().'@example.com','gender'=>'Male','password'=>bcrypt('SecretPass123')]);
        $actor->applicationAccesses()->create(['application_code'=>'management','is_active'=>true]);
        return $actor;
    }

    public function test_staff_tabs_render_branch_scoped_accounts_with_standard_modals(): void
    {
        $owner=$this->actor('OWNER');$hrd=$this->actor('HRD');$finance=$this->actor('FINANCE');
        $foreign=$this->actor('FINANCE',2);$deleted=$this->actor('HRD');$deleted->delete();
        foreach(['hrd'=>'HRD','finance'=>'FINANCE'] as $page=>$role) {
            $response=$this->actingAs($owner)->get('/staff?page='.$page)->assertOk()
                ->assertSee('modalAddRole-'.$page)->assertSee('+ New '.($role==='HRD'?'HRD':'Finance'));
            $response->assertViewHas('page',$page)->assertViewHas('roleAccounts',function($groups) use($hrd,$finance) {
                return $groups->flatten()->pluck('id')->sort()->values()->all()===collect([$hrd->id,$finance->id])->sort()->values()->all();
            });
            $html=$response->getContent();
            $this->assertStringNotContainsString($hrd->password,$html);
            $this->assertStringContainsString('id="'.$page.'-create-password"',$html);
        }
        $this->get('/old-staff?page=hrd')->assertOk()->assertSee('Restore')->assertSee('Force Delete')
            ->assertDontSee('modalAddRole-hrd')->assertViewHas('roleAccounts',function($groups) use($deleted) {
                return $groups->flatten()->pluck('id')->all()===[$deleted->id];
            });
    }
    public function test_administrator_and_owner_have_separate_current_and_old_lists_and_create_forms(): void
    {
        $owner=$this->actor('OWNER');$admin=$this->actor('ADMIN');
        $oldOwner=$this->actor('OWNER');$oldOwner->delete();
        $oldAdmin=$this->actor('ADMIN');$oldAdmin->delete();
        $this->actingAs($owner);
        foreach(['admin','owner'] as $page) {
            $response=$this->get('/staff?page='.$page)->assertOk()->assertDontSee('Administrator &amp; Owner List',false);
            $this->assertSame([$admin->id],$response->viewData('administrator')->pluck('id')->all());
            $this->assertSame([$owner->id],$response->viewData('owners')->pluck('id')->all());
            $dom=new \DOMDocument();@$dom->loadHTML($response->getContent());$xpath=new \DOMXPath($dom);
            $adminTable=$dom->getElementById('staff-admin-table')->textContent;
            $ownerTable=$dom->getElementById('staff-owner-table')->textContent;
            $this->assertStringContainsString($admin->email,$adminTable);
            $this->assertStringNotContainsString($owner->email,$adminTable);
            $this->assertStringContainsString($owner->email,$ownerTable);
            $this->assertStringNotContainsString($admin->email,$ownerTable);
            $this->assertSame('ADMIN',$xpath->query('//*[@id="modalAddAdministrator"]//input[@name="role"]')->item(0)->getAttribute('value'));
            $this->assertSame('OWNER',$xpath->query('//*[@id="modalAddOwner"]//input[@name="role"]')->item(0)->getAttribute('value'));
            $this->assertStringContainsString('show active',$dom->getElementById($page==='admin' ? 'administrator':'ownerAccounts')->getAttribute('class'));
        }
        $response=$this->get('/old-staff?page=owner')->assertOk();
        $this->assertSame([$oldAdmin->id],$response->viewData('administrator')->pluck('id')->all());
        $this->assertSame([$oldOwner->id],$response->viewData('owners')->pluck('id')->all());
        $this->actingAs($admin)->get('/staff?page=admin')->assertOk()->assertDontSee('id="ownerAccounts"',false)
            ->assertViewHas('owners',function($owners) { return $owners->isEmpty(); });
        $this->get('/staff?page=owner')->assertForbidden();
        $this->get('/old-staff?page=owner')->assertForbidden();
    }

    public function test_failed_validation_reopens_only_the_originating_role_form(): void
    {
        $owner=$this->actor('OWNER');$this->actingAs($owner);
        $this->from('/staff?page=finance')->post('/finance-accounts',[
            '_role_form'=>'finance-create','full_name'=>'Draft Finance','email'=>'bad','gender'=>'Female',
            'password'=>'short','password_confirmation'=>'different',
        ])->assertRedirect('/staff?page=finance')->assertSessionHasErrors(['email','password']);
        $response=$this->get('/staff')->assertOk()->assertViewHas('page','finance');
        $dom=new \DOMDocument();@$dom->loadHTML($response->getContent());$xpath=new \DOMXPath($dom);
        $this->assertEquals('Draft Finance',$xpath->query('//*[@id="finance-create-name"]')->item(0)->getAttribute('value'));
        $this->assertEquals('',$xpath->query('//*[@id="hrd-create-name"]')->item(0)->getAttribute('value'));
        $this->assertEquals(1,substr_count($response->getContent(),'getOrCreateInstance(modal).show()'));
        $this->assertStringNotContainsString('value="short"',$response->getContent());
    }
    public function test_old_data_restore_and_permanent_delete_are_scoped_to_role_and_branch(): void
    {
        $this->actingAs($this->actor('OWNER'));
        foreach(['hrd'=>'HRD','finance'=>'FINANCE'] as $prefix=>$role) {
            $account=$this->actor($role);$foreign=$this->actor($role,2);$foreign->delete();
            $wrong=$this->actor('CS');$wrong->delete();
            $this->delete('/'.$prefix.'-accounts/'.$account->id.'/force-delete')->assertNotFound();
            $this->delete('/'.$prefix.'-accounts/'.$account->id)->assertRedirect(route('staff.index',['page'=>$prefix]));
            $this->post('/'.$prefix.'-accounts/'.$account->id.'/restore')->assertRedirect(route('staff.index',['page'=>$prefix]));
            $this->assertNull($account->fresh()->deleted_at);
            foreach([$foreign,$wrong] as $target) {
                $this->post('/'.$prefix.'-accounts/'.$target->id.'/restore')->assertNotFound();
                $this->delete('/'.$prefix.'-accounts/'.$target->id.'/force-delete')->assertNotFound();
            }
            $this->delete('/'.$prefix.'-accounts/'.$account->id)->assertRedirect();
            $this->delete('/'.$prefix.'-accounts/'.$account->id.'/force-delete')->assertRedirect(route('old-staff',['page'=>$prefix]));
            $this->assertNull(User::withTrashed()->find($account->id));
            $this->assertEquals(0,DB::table('user_application_access')->where('user_id',$account->id)->count());
            $this->assertNotNull(User::onlyTrashed()->find($foreign->id));
        }
    }
}
