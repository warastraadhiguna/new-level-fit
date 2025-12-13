<?php

namespace App\Models\Class;

use App\Models\Staff\ClassInstructor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_instructor_id',
        'name',
        'note',
        'capacity',              
        'price'
    ];

    public function classInstructor()
    {
        return $this->belongsTo(ClassInstructor::class);
    }    
}