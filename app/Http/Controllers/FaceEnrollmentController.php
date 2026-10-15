<?php
namespace App\Http\Controllers;
use App\Services\FaceEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
class FaceEnrollmentController extends Controller
{
    private function users(Request $request)
    {
        $branchId = $request->user()->branch_store_id;
        abort_unless($branchId, 403, 'Cabang aktif belum ditentukan.');
        return DB::connection('face_enrollment')->table('users as u')
            ->whereNull('u.deleted_at')
            ->where('u.branch_store_id', $branchId);
    }
    public function index(Request $request)
    {
        $ready = Schema::connection('face_enrollment')->hasTable('face_attendance_employees');
        $query = $this->users($request)->leftJoin('branch_stores as b', 'u.branch_store_id', '=', 'b.id');
        if ($ready) {
            $query->leftJoin('face_attendance_employees as f', 'u.id', '=', 'f.source_user_id');
        }
        $employees = $query->orderBy('u.full_name')->select('u.id', 'u.full_name', 'u.role', 'b.name as branch_name')
            ->selectRaw($ready ? 'f.face_embedding IS NOT NULL AND f.face_model = ? as enrolled' : '0 as enrolled', $ready ? [config('face.model')] : [])->get();
        return view('admin.layouts.wrapper', [
            'title' => 'Pendaftaran Wajah', 'content' => 'admin.face-enrollment.index',
            'employees' => $employees, 'faceReady' => $ready,
        ]);
    }
    public function store(Request $request, FaceEnrollmentService $service)
    {
        $request->validate(['employee_id' => 'required|integer', 'photo' => 'required|string|max:7000000']);
        $employee = $this->users($request)->where('u.id', $request->employee_id)->first();
        abort_unless($employee, 404);
        if (!Schema::connection('face_enrollment')->hasTable('face_attendance_employees')) {
            throw ValidationException::withMessages(['photo' => 'Tabel pendaftaran wajah belum tersedia. Jalankan migration enrollment.']);
        }
        if (!preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#D', $request->photo, $matches)) {
            throw ValidationException::withMessages(['photo' => 'Ambil foto langsung dari kamera.']);
        }
        $image = base64_decode($matches[1], true);
        $info = $image ? @getimagesizefromstring($image) : false;
        if (!$info || $info[2] !== IMAGETYPE_JPEG || strlen($image) > 5*1024*1024 || $info[0]*$info[1] > 12000000) {
            throw ValidationException::withMessages(['photo' => 'Foto kamera tidak valid atau terlalu besar.']);
        }
        $data = $service->enroll($image);
        $db = DB::connection('face_enrollment');
        $db->transaction(function () use ($request, $employee, $data, $db) {
            // Recheck account/branch access after inference, while holding the source row lock.
            $current = $this->users($request)->where('u.id', $employee->id)->lockForUpdate()->first();
            abort_unless($current, 404);
            $branch = $db->table('branch_stores')->where('id', $current->branch_store_id)->value('name');
            $time = now();
            $db->table('face_attendance_employees')->upsert([[
                'source_user_id' => $current->id, 'employee_code' => 'USER-'.$current->id,
                'name' => $current->full_name, 'department' => $current->role.($branch ? ' · '.$branch : ''),
                'face_embedding' => json_encode($data['embedding']), 'face_model' => $data['model'],
                'created_at' => $time, 'updated_at' => $time,
            ]], ['source_user_id'], ['name','department','face_embedding','face_model','updated_at']);
        });
        return response()->json(['message' => 'Wajah '.$employee->full_name.' berhasil didaftarkan.']);
    }
}
