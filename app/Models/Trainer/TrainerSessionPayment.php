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
        'trainer_session_id',
        'note',
        'value',
        'method_payment_id',
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
