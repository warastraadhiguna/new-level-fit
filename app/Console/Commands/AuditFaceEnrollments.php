<?php

namespace App\Console\Commands;

use App\Services\FaceEnrollmentSafety;
use Illuminate\Console\Command;

class AuditFaceEnrollments extends Command
{
    protected $signature='face:audit-enrollments {--employee= : Employee ID to inspect}';
    protected $description='Read-only audit of face samples registered to different active employees';

    public function handle(FaceEnrollmentSafety $safety)
    {
        $employee=$this->option('employee');
        if ($employee!==null && (!ctype_digit((string)$employee) || (int)$employee<1)) {
            $this->error('Employee ID must be a positive integer.');return 1;
        }
        $threshold=$safety->threshold();$samples=$safety->samples();$rows=[];
        foreach ($samples as $i=>$a) {
            foreach ($samples as $j=>$b) {
                if ($j<=$i || $a->employee_id===$b->employee_id) continue;
                if ($employee!==null && (int)$a->employee_id!==(int)$employee && (int)$b->employee_id!==(int)$employee) continue;
                $score=$safety->similarity(json_decode($a->face_embedding,true),json_decode($b->face_embedding,true));
                if ($score===null || $score<$threshold) continue;
                $rows[]=[$a->employee_id,$a->employee_code,$a->branch_store_id,$a->id,
                    $b->employee_id,$b->employee_code,$b->branch_store_id,$b->id,number_format($score,4)];
            }
        }
        $this->line('Read-only comparison. Similarity is a review flag, not proof of an incorrect identity.');
        $this->line('Threshold: '.$threshold.' | Active samples: '.$samples->count());
        $this->table(['Employee A','Code A','Branch A','Sample A','Employee B','Code B','Branch B','Sample B','Similarity'],$rows);
        $this->info(count($rows).' sample pairs require review. No data was changed.');
        return 0;
    }
}
