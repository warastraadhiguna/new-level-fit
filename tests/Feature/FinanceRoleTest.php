<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TipTapTransferImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Tests\TestCase;

class FinanceRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->integer('branch_store_id'); $t->string('full_name'); $t->string('email')->unique();
            $t->string('role'); $t->string('gender'); $t->string('password'); $t->rememberToken(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('branch_stores', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('user_application_access', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('application_code'); $t->boolean('is_active'); $t->timestamps();
        });
        foreach (['personal_trainers','class_instructors'] as $table) Schema::create($table, function (Blueprint $t) {
            $t->id(); $t->softDeletes();
        });
        Schema::create('trashes', function (Blueprint $t) {
            $t->id(); $t->string('kind'); $t->integer('original_id'); $t->integer('member_id');
            $t->string('label'); $t->timestamp('deleted_at');
        });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
    }

    private function actor(string $role): User
    {
        $actor=User::create(['branch_store_id'=>1,'full_name'=>$role.' User','email'=>uniqid().'@example.com',
            'role'=>$role,'gender'=>'Male','password'=>bcrypt('Secret123')]);
        $actor->applicationAccesses()->create(['application_code'=>'management','is_active'=>true]);
        return $actor;
    }

    private function payload(array $extra=[]): array
    {
        return array_merge(['branch_store_id'=>2,'full_name'=>'Finance Staff','email'=>'finance@example.com','gender'=>'Female',
            'role'=>'FINANCE','password'=>'Secret123','application_access'=>['management'],'page'=>'finance'], $extra);
    }

    public function test_owner_can_manage_finance_from_staff_and_deleted_accounts_cannot_login(): void
    {
        $owner=$this->actor('OWNER'); $this->actingAs($owner);
        $this->get('/staff?page=finance')->assertOk()->assertSee('Finance List')->assertSee('Create Finance');
        $this->post('/administrator', $this->payload())->assertSessionHasNoErrors()->assertRedirect('/staff?page=finance');
        $finance=User::where('email','finance@example.com')->firstOrFail();
        $this->assertSame('FINANCE', $finance->role);
        $this->assertSame(2, $finance->branch_store_id);
        $this->assertTrue(Hash::check('Secret123', $finance->password));
        $this->assertTrue($finance->hasApplicationAccess('management'));
        $this->get('/staff?page=finance')->assertOk()->assertSee('finance@example.com');
        $this->put('/administrator/'.$finance->id, $this->payload(['full_name'=>'Updated Finance','password'=>'']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Updated Finance', $finance->fresh()->full_name);
        $this->assertTrue(Hash::check('Secret123', $finance->fresh()->password));
        $this->delete('/administrator/'.$finance->id, ['page'=>'finance'])->assertRedirect();
        $this->assertSoftDeleted('users',['id'=>$finance->id]);
        $this->post('/logout');
        $this->post('/login',['email'=>$finance->email,'password'=>'Secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($owner)->get('/old-staff?page=finance')->assertOk()->assertSee('finance@example.com');
        $this->get('/restore-administrator/'.$finance->id)->assertRedirect();
        $this->assertNotNull(User::find($finance->id));
    }

    public function test_other_roles_cannot_manage_finance_accounts(): void
    {
        $finance=$this->actor('FINANCE');
        $this->actingAs($this->actor('ADMIN'));
        $this->get('/staff?page=finance')->assertOk()->assertDontSee('Finance List')->assertDontSee($finance->email);
        $this->post('/administrator', $this->payload())->assertSessionHasErrors('role');
        $this->put('/administrator/'.$finance->id, $this->payload(['email'=>$finance->email]))->assertNotFound();
        $this->delete('/administrator/'.$finance->id)->assertForbidden();
        foreach (['CS','CSPOS','FC','PT','FINANCE'] as $role) {
            $this->actingAs($this->actor($role))->post('/administrator',$this->payload())->assertForbidden();
            $this->put('/administrator/'.$finance->id,$this->payload())->assertForbidden();
            $this->delete('/administrator/'.$finance->id)->assertForbidden();
        }
        $this->assertNotNull(User::find($finance->id));
    }

    public function test_finance_lands_on_tip_tap_and_only_sees_workflow_menus_and_no_owner_actions(): void
    {
        $finance=$this->actor('FINANCE');
        $this->post('/login',['email'=>$finance->email,'password'=>'Secret123'])->assertRedirect(route('tip-tap.index'));
        DB::table('trashes')->insert(['kind'=>'member','original_id'=>1,'member_id'=>1,'label'=>'Archived Member','deleted_at'=>now()]);
        $this->get('/')->assertRedirect(route('tip-tap.index'));
        $this->get('/tip-tap')->assertOk()->assertSee('Import Data Baru dari Master')->assertSee('Archived Member')
            ->assertSee('href="'.route('members.index').'"',false)
            ->assertDontSee('action="'.route('tip-tap.restore',1).'"',false)
            ->assertDontSee('action="'.route('tip-tap.purge',1).'"',false)
            ->assertDontSee('action="'.route('tip-tap.purge-all').'"',false)
            ->assertDontSee('href="'.route('staff.index',['page'=>'cs']).'"',false);
        foreach (['/staff','/secret-branch-store','/member-active','/pos','/member/1/edit'] as $url) $this->get($url)->assertForbidden();
        $this->post('/member',[])->assertForbidden();
        $this->post('/member-check-in',[])->assertForbidden();
        $this->post('/tip-tap/1/restore')->assertForbidden();
        $this->delete('/tip-tap/1/purge',['confirmation'=>'HAPUS PERMANEN'])->assertForbidden();
        $this->delete('/tip-tap/purge-all',['confirmation'=>'HAPUS SEMUA PERMANEN'])->assertForbidden();
        \Laravel\Sanctum\Sanctum::actingAs($finance);
        $this->postJson('/api/gate/check-in',[])->assertForbidden();
    }

    public function test_finance_can_import_through_existing_transfer_service(): void
    {
        $this->actingAs($this->actor('FINANCE'));
        $this->mock(TipTapTransferImportService::class, function ($mock) {
            $mock->shouldReceive('import')->once()->with(\Mockery::type(UploadedFile::class))->andReturn([
                'from_exclusive'=>'2026-11-01','until_inclusive'=>'2026-11-12','inserted'=>1,'updated'=>0,'skipped'=>0,
                'references'=>0,'files'=>0,'source_missing_files'=>0,'resolved_member_identifiers'=>0,
            ]);
        });
        $this->post('/tip-tap/import',['confirmation'=>'IMPORT DATA','transfer_file'=>UploadedFile::fake()->create('transfer.zip',1)])
            ->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('success');
    }

    public function test_finance_requires_active_management_access(): void
    {
        $finance=$this->actor('FINANCE');
        $finance->applicationAccesses()->update(['is_active'=>false]);
        $this->post('/login',['email'=>$finance->email,'password'=>'Secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($finance)->get('/tip-tap')->assertForbidden();
    }
}
