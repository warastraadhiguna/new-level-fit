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
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }    

    public function member()
    {
        return $this->belongsTo(Member::class);
    }      
}