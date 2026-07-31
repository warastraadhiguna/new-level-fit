<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'pos_suppliers';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
