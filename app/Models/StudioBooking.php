<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_date',
        'duration_time',
        'booking_code',
        'name',
        'phone_number',
        'studio_name',
        'status'
    ];

    protected $hidden = [];
}
