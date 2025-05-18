<?php

namespace App\Models\Member;

use App\Models\MethodPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MemberRegistrationPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'member_registration_id',
        'note',
        'method_payment_id',
        'value',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class);
    }
}
