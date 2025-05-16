<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPosService extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'gender',
        'club'
    ];
}
