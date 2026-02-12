<?php

namespace App\Models\Class;

use App\Models\Member\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class ClassDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_schedule_id',
        'user_id',
        'member_id',
        'name',        
        'phone',   
        'email',
        'canceled_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }    

    public function member()
    {
        return $this->belongsTo(Member::class);
    }      
	
    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }     	
}