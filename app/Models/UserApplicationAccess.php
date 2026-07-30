<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApplicationAccess extends Model
{
    protected $table = 'user_application_access';

    protected $fillable = [
        'user_id',
        'application_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
