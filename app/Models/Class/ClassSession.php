<?php

namespace App\Models\Class;

use App\Models\BranchStore;
use App\Models\Staff\ClassInstructor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_instructor_id',
        'branch_store_id',		
        'name',
        'note',
        'capacity',              
        'price',
		'day', 
		'time_start',
		'time_end',		
		'is_active'
    ];

    public function classInstructor()
    {
        return $this->belongsTo(ClassInstructor::class);
    }    
	
    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class);
    }    	
}