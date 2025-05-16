<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberPackageCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_category_name',
    ];

    protected $hidden = [];
}
