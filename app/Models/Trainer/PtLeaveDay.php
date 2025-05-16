<?php

namespace App\Models\Trainer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtLeaveDay extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'trainer_session_id',
        'submission_date',
        'price',
        'days'
    ];

    public function memberRegistrations()
    {
        return $this->belongsTo(TrainerSession::class, 'trainer_session_id', 'id');
    }
}
