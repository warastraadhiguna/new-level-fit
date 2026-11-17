<?php
namespace Tests\Feature;

use App\Services\FaceEnrollmentService;
use Illuminate\Support\Facades\{DB,Http,Log,Route};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalInformationPrivacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Log::spy();
    }
    public function test_unexpected_errors_hide_technical_details_even_with_debug_enabled(): void
    {
        config(['app.debug'=>true]);
        Route::get('/privacy-test-error',function() { throw new \RuntimeException('SQLSTATE secret_database password=private-key /srv/internal/file.php'); });
        $json=$this->getJson('/privacy-test-error')->assertStatus(500)->assertHeader('Cache-Control','no-store, private');
        $this->assertSame(['message'],array_keys($json->json()));
        $html=$this->get('/privacy-test-error')->assertStatus(500)->assertSee('Silakan coba kembali');
        foreach ([$json,$html] as $response) foreach (['SQLSTATE','secret_database','private-key','file.php','stack trace'] as $secret) $response->assertDontSee($secret);
    }

    public function test_missing_resources_hide_model_names_and_validation_still_returns_field_errors(): void
    {
        config(['app.debug'=>true]);
        Route::get('/privacy-test-missing',function() { throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(\App\Models\User::class,[123]); });
        $this->getJson('/privacy-test-missing')->assertNotFound()->assertJsonPath('message','Halaman tidak ditemukan.')->assertDontSee('App\\Models')->assertDontSee('123');
        Route::post('/privacy-test-validation',function() { request()->validate(['name'=>'required']); });
        $this->postJson('/privacy-test-validation')->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_unavailable_pages_do_not_expose_setup_instructions(): void
    {
        Route::get('/privacy-test-unavailable',function() { abort(503,'Run migration for database internal_table'); });
        $this->getJson('/privacy-test-unavailable')->assertStatus(503)->assertDontSee('migration')->assertDontSee('internal_table');
        $this->get('/privacy-test-unavailable')->assertStatus(503)->assertDontSee('migration')->assertDontSee('internal_table');
    }

    public function test_revenue_view_does_not_announce_disabled_pos_or_offer_it_as_a_filter(): void
    {
        $this->actingAs(new \App\Models\User(['role'=>'OWNER','branch_store_id'=>1]));
        $data=['summary'=>collect(),'transactions'=>new \Illuminate\Pagination\LengthAwarePaginator([],0,20),
            'grandTotal'=>0,'transactionCount'=>0,'fromDate'=>'2026-11-01','toDate'=>'2026-11-30',
            'search'=>'','viewMode'=>'detail','category'=>'','perPage'=>20,'includePos'=>false,'errors'=>new \Illuminate\Support\ViewErrorBag()];
        $view=$this->view('admin.revenue-report.index',$data)->assertSee('Revenue Report')->assertDontSee('POS is disabled')->assertDontSee('value="pos"',false);
    }

    public function test_enrollment_does_not_forward_service_errors_but_keeps_safe_photo_guidance(): void
    {
        Http::fake(['*'=>Http::sequence()->push(['success'=>false,'message'=>'SQLSTATE /private/model.onnx password=secret'],503)->push(['success'=>false,'code'=>'face_blurry','message'=>'private diagnostic'],422)]);
        try { app(FaceEnrollmentService::class)->enroll('photo');$this->fail('Expected validation error'); }
        catch (ValidationException $e) {
            $text=json_encode($e->errors());$this->assertStringNotContainsString('SQLSTATE',$text);$this->assertStringNotContainsString('password',$text);
        }
        try { app(FaceEnrollmentService::class)->enroll('photo');$this->fail('Expected validation error'); }
        catch (ValidationException $e) { $this->assertStringContainsString('Foto buram',$e->errors()['photo'][0]); }
    }
}
