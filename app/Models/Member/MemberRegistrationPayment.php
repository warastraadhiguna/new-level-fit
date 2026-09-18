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
        'branch_store_id',
        'member_registration_id',
        'note',
        'method_payment_id',
        'value',
        'received_amount',
        'user_id',
    ];

    protected static function booted()
    {
        static::creating(function (MemberRegistrationPayment $payment) {
            if ($payment->branch_store_id) {
                return;
            }

            $payment->branch_store_id = User::whereKey($payment->user_id)->value('branch_store_id');

            if (! $payment->branch_store_id && $payment->member_registration_id) {
                $payment->branch_store_id = MemberRegistration::query()
                    ->join('members', 'members.id', '=', 'member_registrations.member_id')
                    ->where('member_registrations.id', $payment->member_registration_id)
                    ->value('members.branch_store_id');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class);
    }

    public function memberRegistration()
    {
        return $this->belongsTo(MemberRegistration::class);
    }
}
