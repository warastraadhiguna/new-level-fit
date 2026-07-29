<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;

class MemberRegistrationInstallment extends Model
{
    protected $fillable = [
        'member_registration_id', 'month_number', 'payment_order', 'type',
        'due_date', 'amount', 'paid_amount', 'status', 'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function registration()
    {
        return $this->belongsTo(MemberRegistration::class, 'member_registration_id');
    }
}
