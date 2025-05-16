<?php

namespace App\Models\Member;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInMember extends Model
{
    use HasFactory;
    protected $fillable = [
        'member_registration_id',
        'check_in_time',
        'check_out_time',
        'user_id'
    ];

    protected $hidden = [];

    public function memberRegistration()
    {
        return $this->belongsTo(MemberRegistration::class, 'member_registration_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
