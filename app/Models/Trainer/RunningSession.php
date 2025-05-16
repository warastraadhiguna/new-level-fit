<?php

namespace App\Models\Trainer;

use App\Models\Member\Member;
use App\Models\Staff\CustomerService;
use App\Models\Staff\PersonalTrainer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunningSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_time',
        'member_id',
        'trainer_package_id',
        'session_total',
        'check_in',
        'check_out',
        'status',
        'personal_trainer_id',
        'customer_service_id',
        'description'
    ];

    protected $hidden = [];

    public function members()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }

    public function trainerPackages()
    {
        return $this->belongsTo(TrainerPackage::class, 'trainer_package_id', 'id');
    }

    public function personalTrainers()
    {
        return $this->belongsTo(PersonalTrainer::class, 'personal_trainer_id', 'id');
    }

    public function customerServices()
    {
        return $this->belongsTo(CustomerService::class, 'customer_service_id', 'id');
    }
}
