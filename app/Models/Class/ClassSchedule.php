<?php

namespace App\Models\Class;

use App\Models\BranchStore;
use App\Models\Staff\ClassInstructor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_session_id',	
        'class_instructor_id',
		'class_date',
        'name',
        'note',
        'price',        
        'capacity',   
        'real_capacity',
		'time_start',
		'time_end',				
        'is_active'
    ];

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }   

    public function classInstructor()
    {
        return $this->belongsTo(ClassInstructor::class);
    }   

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class);
    }      

    public function classDetails()
    {
        return $this->hasMany(ClassDetail::class);
    }   
}