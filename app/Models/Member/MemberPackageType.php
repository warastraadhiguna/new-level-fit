<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberPackageType extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_type_name',
    ];

    protected $hidden = [];
}
