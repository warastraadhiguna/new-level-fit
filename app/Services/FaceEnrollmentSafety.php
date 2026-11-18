<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FaceEnrollmentSafety
{
    public function threshold(): float
    {
        $threshold = (float) config('face.recognition_threshold', 0.45);
        if (!is_finite($threshold) || $threshold <= 0 || $threshold > 1) {
            throw ValidationException::withMessages(['photo'=>'Pendaftaran wajah belum dapat diproses. Hubungi pengelola.']);
        }
        return $threshold;
    }

    public function similarity($a, $b): ?float
    {
        if (!is_array($a) || !is_array($b) || array_keys($a)!==range(0,127) || array_keys($b)!==range(0,127)) return null;
        $dot=0;$normA=0;$normB=0;
        foreach ($a as $i=>$value) {
            if (!is_numeric($value) || !is_numeric($b[$i]) || !is_finite((float)$value) || !is_finite((float)$b[$i])) return null;
            $dot+=$value*$b[$i];$normA+=$value*$value;$normB+=$b[$i]*$b[$i];
        }
        if ($normA<=0 || $normB<=0 || !is_finite($normA) || !is_finite($normB)) return null;
        return max(-1.0,min(1.0,$dot/(sqrt($normA)*sqrt($normB))));
    }

    public function samples()
    {
        return DB::connection('face_enrollment')->table('face_attendance_samples as s')
            ->join('face_attendance_employees as p','p.id','=','s.face_profile_id')
            ->join('employees as e','e.id','=','p.source_employee_id')
            ->whereNull('e.deleted_at')->where('e.is_active',true)->where('s.face_model',config('face.model'))
            ->select('s.id','s.face_embedding','s.face_profile_id','e.id as employee_id','e.employee_code','e.branch_store_id','e.all_branch')->get();
    }

    public function assertDistinct(array $probe, int $employeeId, int $branchId, bool $allBranch): void
    {
        $threshold=$this->threshold();
        foreach ($this->samples() as $sample) {
            if ((int)$sample->employee_id===$employeeId) continue;
            // Compare employees that can actually appear together at a kiosk.
            if (!$allBranch && !$sample->all_branch && (int)$sample->branch_store_id!==$branchId) continue;
            $score=$this->similarity($probe,json_decode($sample->face_embedding,true));
            if ($score!==null && $score>=$threshold) {
                Log::warning('Face enrollment identity collision',['employee_id'=>$employeeId,
                    'other_employee_id'=>$sample->employee_id,'sample_id'=>$sample->id,'score'=>$score]);
                throw ValidationException::withMessages(['photo'=>'Wajah terlalu mirip dengan pendaftaran pegawai lain. Pendaftaran tidak disimpan. Hubungi pengelola untuk memeriksa identitas dan contoh wajah.']);
            }
        }
    }
}
