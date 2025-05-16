<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveDay extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'member_registration_id',
        'submission_date',
        'price',
        'days',
        'leave_day_continue_id'
    ];

    public function memberRegistrations()
    {
        return $this->belongsTo(MemberRegistration::class, 'member_registration_id', 'id');
    }
}
