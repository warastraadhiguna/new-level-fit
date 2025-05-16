<?php

namespace App\Models\Trainer;

use App\Models\Staff\PersonalTrainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInTrainerSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_session_id',
        'check_in_time',
        'check_out_time',
        'duration',
        'pt_id',
        'user_id'
    ];

    protected $hidden = [];

    public function trainerSession()
    {
        return $this->belongsTo(TrainerSession::class, 'trainer_session_id', 'id');
    }

    public function personalTrainer()
    {
        return $this->belongsTo(PersonalTrainer::class, 'pt_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
