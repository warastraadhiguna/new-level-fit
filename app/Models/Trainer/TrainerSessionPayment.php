<?php

namespace App\Models\Trainer;

use App\Models\MethodPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerSessionPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_store_id',
        'trainer_session_id',
        'note',
        'value',
        'received_amount',
        'method_payment_id',
        'user_id',
    ];

    protected static function booted()
    {
        static::creating(function (TrainerSessionPayment $payment) {
            if ($payment->branch_store_id) {
                return;
            }

            $payment->branch_store_id = User::whereKey($payment->user_id)->value('branch_store_id');

            if (! $payment->branch_store_id && $payment->trainer_session_id) {
                $payment->branch_store_id = TrainerSession::whereKey($payment->trainer_session_id)
                    ->value('branch_store_id');
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

    public function trainerSession()
    {
        return $this->belongsTo(TrainerSession::class);
    }
}
