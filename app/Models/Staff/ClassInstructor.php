<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassInstructor extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'full_name',
        'gender',
		'email',
        'phone_number',
        'description',
        'google_id', 
        'avatar',
        'last_login_at'				
    ];
}