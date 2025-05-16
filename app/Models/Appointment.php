<?php

namespace App\Models;

use App\Models\Member\Member;
use App\Models\Staff\CustomerService;
use App\Models\Staff\FitnessConsultant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_date',
        'member_id',
    ];

    protected $hidden = [];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
