<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Employee extends Model
{
    use SoftDeletes;
    protected $connection='face_enrollment';
    public const POSITION_CATEGORIES=['PT'=>'PT','CS'=>'CS','HR'=>'HR','FINANCE'=>'Finance'];
    protected $fillable=['employee_code','branch_store_id','full_name','born','position','phone','is_active',
        'photo_path','ktp_photo_path','position_category','employment_start_date','resign_date','note','all_branch'];
    protected $casts=['is_active'=>'boolean','all_branch'=>'boolean','born'=>'date','employment_start_date'=>'date','resign_date'=>'date'];

    public function getEmploymentDurationAttribute(): string
    {
        if (!$this->employment_start_date) return '-';
        $timezone=config('face.attendance_timezone') ?: 'Asia/Jakarta';
        $start=\Carbon\Carbon::parse($this->employment_start_date->toDateString(),$timezone)->startOfDay();
        $end=\Carbon\Carbon::today($timezone);
        if ($this->resign_date) {
            $resigned=\Carbon\Carbon::parse($this->resign_date->toDateString(),$timezone)->startOfDay();
            if ($resigned->lt($end)) $end=$resigned;
        }
        if ($start->gt($end)) return '-';
        $duration=$start->diff($end);
        $parts=[];
        if ($duration->y) $parts[]=$duration->y.' tahun';
        if ($duration->m) $parts[]=$duration->m.' bulan';
        $parts[]=$duration->d.' hari';
        return implode(' ',$parts);
    }

    public function scopeAvailableInBranch($query, int $branch)
    {
        return $query->where(function($scope) use($branch) {
            $scope->where('branch_store_id',$branch)->orWhere('all_branch',true);
        });
    }
}
