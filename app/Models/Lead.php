<?php

namespace App\Models;

use App\Models\Staff\CustomerService;
use App\Models\Staff\FitnessConsultant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_time',
        'full_name',
        'guest_code',
        'phone_number',
        'email',
        'address',
        'source',
        'fc_id',
        'cs_id'
    ];

    protected $hidden = [];

    public function fitnessConsultants()
    {
        return $this->belongsTo(FitnessConsultant::class, 'fc_id', 'id');
    }
    public function customerServices()
    {
        return $this->belongsTo(CustomerService::class, 'cs_id', 'id');
    }
}
