<?php
namespace App\Http\Controllers;
use App\Services\FaceEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;
class FaceEnrollmentController extends Controller
{
    private function employees(Request $request)
    {
        $branchId=$request->user()->branch_store_id;
        abort_unless($branchId,403,'Cabang aktif belum ditentukan.');
        return DB::connection('face_enrollment')->table('employees as u')
            ->whereNull('u.deleted_at')->where('u.is_active',true)->where(function($scope) use($branchId) {
                $scope->where('u.branch_store_id',$branchId)->orWhere('u.all_branch',true);
            });
    }
    private function ready(): bool
    {
        $schema=Schema::connection('face_enrollment');
        return $schema->hasTable('face_attendance_employees') && $schema->hasTable('face_attendance_samples');
    }
    private function metadata($profileId): array
    {
        if (!$profileId) return [];
        return DB::connection('face_enrollment')->table('face_attendance_samples')->where('face_profile_id',$profileId)
            ->orderBy('slot')->get(['id','slot','pose','face_model','created_at'])->map(function($s) {
                return ['id'=>$s->id,'slot'=>$s->slot,'pose'=>$s->pose,'compatible'=>$s->face_model===config('face.model'),'created_at'=>$s->created_at];
            })->all();
    }
    public function index(Request $request)
    {
        $ready=$this->ready();
        $query=$this->employees($request)->leftJoin('branch_stores as b','u.branch_store_id','=','b.id');
        if ($ready) $query->leftJoin('face_attendance_employees as f','u.id','=','f.source_employee_id');
        $employees=$query->orderBy('u.full_name')->select('u.id','u.full_name','u.position','b.name as branch_name')
            ->selectRaw($ready ? 'f.id as profile_id':'NULL as profile_id')->get();
        $counts=$ready ? DB::connection('face_enrollment')->table('face_attendance_samples')
            ->whereIn('face_profile_id',$employees->pluck('profile_id')->filter())->select('face_profile_id')
            ->selectRaw('COUNT(*) as total')->selectRaw('SUM(CASE WHEN face_model = ? THEN 1 ELSE 0 END) as valid_count',[config('face.model')])
            ->groupBy('face_profile_id')->get()->keyBy('face_profile_id') : collect();
        foreach ($employees as $employee) {
            $count=$counts->get($employee->profile_id);
            $employee->sample_count=$count ? (int)$count->total : 0;
            $employee->enrolled=$count && $count->valid_count>0;
        }
        return view('admin.layouts.wrapper',['title'=>'Pendaftaran Wajah','content'=>'admin.face-enrollment.index','employees'=>$employees,'faceReady'=>$ready]);
    }
    public function samples(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer']);
        $employee=$this->employees($request)->where('u.id',$request->employee_id)->first();abort_unless($employee,404);
        abort_unless($this->ready(),503,'Tabel contoh wajah belum tersedia. Jalankan SQL contoh wajah.');
        $profile=DB::connection('face_enrollment')->table('face_attendance_employees')->where('source_employee_id',$employee->id)->first();
        $samples=$this->metadata($profile ? $profile->id : null);
        return response()->json(['samples'=>$samples,'sample_count'=>count($samples)])->header('Cache-Control','no-store, private');
    }
    private function compatible(array $probe, $samples): bool
    {
        $valid=0;$best=-1;$probeNorm=sqrt(array_sum(array_map(function($v) { return $v*$v; },$probe)));
        foreach ($samples as $sample) {
            if ($sample->face_model!==config('face.model')) continue;
            $reference=json_decode($sample->face_embedding,true);
            if (!is_array($reference) || count($reference)!==128 || array_keys($reference)!==range(0,127)) continue;
            $dot=0;$norm=0;
            foreach ($reference as $i=>$value) {
                if (!is_numeric($value) || !is_finite((float)$value)) { $norm=0;break; }
                $dot+=$probe[$i]*$value;$norm+=$value*$value;
            }
            if ($norm<=0 || !is_finite($norm)) continue;
            $valid++;$best=max($best,$dot/($probeNorm*sqrt($norm)));
        }
        return !$valid || $best>=0.363;
    }
    public function store(Request $request, FaceEnrollmentService $service)
    {
        $request->validate(['employee_id'=>'required|integer','photo'=>'required|string|max:7000000','pose'=>'nullable|in:front,left,right,additional']);
        $employee=$this->employees($request)->where('u.id',$request->employee_id)->first();abort_unless($employee,404);
        if (!$this->ready()) throw ValidationException::withMessages(['photo'=>'Tabel contoh wajah belum tersedia. Jalankan SQL contoh wajah.']);
        $db=DB::connection('face_enrollment');
        $existing=$db->table('face_attendance_employees')->where('source_employee_id',$employee->id)->value('id');
        if ($existing && $db->table('face_attendance_samples')->where('face_profile_id',$existing)->count()>=5) {
            throw ValidationException::withMessages(['photo'=>'Maksimal 5 contoh wajah. Hapus salah satu contoh sebelum menambahkan yang baru.']);
        }
        if (!preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#D',$request->photo,$matches)) throw ValidationException::withMessages(['photo'=>'Ambil foto langsung dari kamera.']);
        $image=base64_decode($matches[1],true);$info=$image ? @getimagesizefromstring($image) : false;
        if (!$info || $info[2]!==IMAGETYPE_JPEG || strlen($image)>5*1024*1024 || $info[0]*$info[1]>12000000) throw ValidationException::withMessages(['photo'=>'Foto kamera tidak valid atau terlalu besar.']);
        $data=$service->enroll($image);
        $samples=$db->transaction(function() use($request,$employee,$data,$db) {
            $current=$this->employees($request)->where('u.id',$employee->id)->lockForUpdate()->first();abort_unless($current,404);
            $branch=$db->table('branch_stores')->where('id',$current->branch_store_id)->value('name');$time=now();
            $db->table('face_attendance_employees')->upsert([[
                'source_employee_id'=>$current->id,'employee_code'=>'EMP-'.$current->id,
                'name'=>$current->full_name,'department'=>($current->position ?: 'Pegawai').($branch ? ' · '.$branch : ''),
                'face_embedding'=>json_encode($data['embedding']),'face_model'=>$data['model'],'created_at'=>$time,'updated_at'=>$time,
            ]],['source_employee_id'],['name','department','updated_at']);
            $profileId=$db->table('face_attendance_employees')->where('source_employee_id',$current->id)->value('id');
            $existing=$db->table('face_attendance_samples')->where('face_profile_id',$profileId)->orderBy('slot')->lockForUpdate()->get();
            if ($existing->count()>=5) throw ValidationException::withMessages(['photo'=>'Maksimal 5 contoh wajah. Hapus salah satu contoh terlebih dahulu.']);
            if (!$this->compatible($data['embedding'],$existing)) throw ValidationException::withMessages(['photo'=>'Contoh terlalu berbeda dari wajah terdaftar. Pastikan pegawai dan pencahayaan benar. Jika perlu pendaftaran ulang, Owner/Admin dapat menghapus contoh lama setelah memastikan identitas.']);
            $slot=1;while ($existing->contains('slot',$slot)) $slot++;
            $db->table('face_attendance_samples')->insert(['face_profile_id'=>$profileId,'slot'=>$slot,'pose'=>$request->input('pose','front') ?: 'front',
                'face_embedding'=>json_encode($data['embedding']),'face_model'=>$data['model'],'created_at'=>$time,'updated_at'=>$time]);
            // Keep the old single-vector field compatible with historical readers.
            $primary=$db->table('face_attendance_samples')->where('face_profile_id',$profileId)->orderBy('slot')->first();
            $db->table('face_attendance_employees')->where('id',$profileId)->update(['face_embedding'=>$primary->face_embedding,'face_model'=>$primary->face_model,'updated_at'=>$time]);
            return $this->metadata($profileId);
        });
        return response()->json(['message'=>'Contoh wajah '.$employee->full_name.' berhasil ditambahkan ('.count($samples).'/5).','samples'=>$samples,'sample_count'=>count($samples)]);
    }
    public function destroySample(Request $request, $sample)
    {
        abort_unless($this->ready(),503,'Tabel contoh wajah belum tersedia.');
        $request->validate(['employee_id'=>'required|integer']);
        $db=DB::connection('face_enrollment');
        $samples=$db->transaction(function() use($request,$sample,$db) {
            $employee=$this->employees($request)->where('u.id',$request->employee_id)->lockForUpdate()->first();abort_unless($employee,404);
            $profileId=$db->table('face_attendance_employees')->where('source_employee_id',$employee->id)->value('id');abort_unless($profileId,404);
            $query=$db->table('face_attendance_samples')->where('face_profile_id',$profileId)->where('id',$sample);abort_unless($query->exists(),404);$query->delete();
            $primary=$db->table('face_attendance_samples')->where('face_profile_id',$profileId)->orderBy('slot')->first();
            $db->table('face_attendance_employees')->where('id',$profileId)->update(['face_embedding'=>$primary ? $primary->face_embedding : null,'face_model'=>$primary ? $primary->face_model : null,'updated_at'=>now()]);
            return $this->metadata($profileId);
        });
        return response()->json(['message'=>'Contoh wajah dihapus.','samples'=>$samples,'sample_count'=>count($samples)]);
    }
}
