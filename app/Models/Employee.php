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

    public function scopeAvailableInBranch($query, int $branch)
    {
        return $query->where(function($scope) use($branch) {
            $scope->where('branch_store_id',$branch)->orWhere('all_branch',true);
        });
    }
}
