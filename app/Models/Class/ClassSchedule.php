<?php

namespace App\Models\Class;

use App\Models\Staff\ClassInstructor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_instructor_id',
        'name',
        'note',
        'price',        
        'capacity',   
        'real_capacity',
        'is_active'
    ];

    public function classInstructor()
    {
        return $this->belongsTo(ClassInstructor::class);
    }    
}