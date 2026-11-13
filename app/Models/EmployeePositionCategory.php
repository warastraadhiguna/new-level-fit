<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EmployeePositionCategory extends Model
{
    protected $connection='face_enrollment';
    protected $fillable=['code','name','is_active'];
    protected $casts=['is_active'=>'boolean'];

    public static function ready(): bool
    {
        return Schema::connection('face_enrollment')->hasTable('employee_position_categories');
    }

    public static function catalog()
    {
        if (self::ready()) return self::orderBy('name')->get()->keyBy('code');
        // Keep existing employee forms usable until the shared database is upgraded.
        return collect(Employee::POSITION_CATEGORIES)->map(fn($name,$code)=>new self(['code'=>$code,'name'=>$name,'is_active'=>true]));
    }
}
