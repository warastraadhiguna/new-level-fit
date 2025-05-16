<?php

namespace App\Models\Staff;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitnessConsultant extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone_number',
        'role',
        'gender',
        'address',
        'description',
        'user_id'
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
