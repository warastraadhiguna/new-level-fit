<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AttendanceDeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function($request,$next) {
            abort_unless($request->user() && $request->user()->isOwner(),403,'Hanya Owner yang dapat mengelola perangkat absensi.');
            abort_unless($request->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
            return $next($request);
        });
    }
    private function ownerContext(Request $request): array
    {
        return [
            'owner_id'=>$request->user()->id,
            'owner_name'=>$request->user()->full_name,
            'owner_login_branch_store_id'=>$request->user()->branch_store_id,
            'owner_login_branch_name'=>optional($request->user()->branchStore)->name,
            'owner_recorded_at'=>now('UTC'),
        ];
    }
    public function index(Request $request)
    {
        $device=DB::connection('face_enrollment')->table('face_attendance_devices')->where('branch_store_id',$request->user()->branch_store_id)->first();
        return response()->view('admin.layouts.wrapper',['title'=>'Perangkat Absensi','content'=>'admin.attendance-device.index','device'=>$device])->header('Cache-Control','no-store, private');
    }
    public function pair(Request $request)
    {
        $request->validate(['label'=>'required|string|max:100']);
        $code=strtoupper(bin2hex(random_bytes(6)));$now=now('UTC');
        $db=DB::connection('face_enrollment');
        $db->transaction(function() use($request,$db,$code,$now) {
            $branch=$db->table('branch_stores')->where('id',$request->user()->branch_store_id)->lockForUpdate()->first();abort_unless($branch,404);
            $row=$db->table('face_attendance_devices')->where('branch_store_id',$branch->id)->lockForUpdate()->first();
            $values=['pairing_hash'=>hash('sha256',$code),'pairing_label'=>$request->label,'pairing_expires_at'=>$now->copy()->addMinutes(5),'updated_at'=>$now]+$this->ownerContext($request);
            if($row) $db->table('face_attendance_devices')->where('id',$row->id)->update($values);
            else $db->table('face_attendance_devices')->insert($values+['branch_store_id'=>$branch->id,'created_at'=>$now]);
        });
        return redirect()->route('attendance-device.index')->with('pairing_code',implode('-',str_split($code,4)))->with('message','Kode aktivasi berlaku 5 menit dan hanya dapat digunakan sekali.');
    }
    public function revoke(Request $request)
    {
        DB::connection('face_enrollment')->table('face_attendance_devices')->where('branch_store_id',$request->user()->branch_store_id)
            ->update(['token_hash'=>null,'expires_at'=>null,'pairing_hash'=>null,'pairing_expires_at'=>null,'pairing_label'=>null,'updated_at'=>now('UTC')]+$this->ownerContext($request));
        return redirect()->route('attendance-device.index')->with('message','Akses perangkat dan kode aktivasi cabang ini sudah dicabut.');
    }
}
