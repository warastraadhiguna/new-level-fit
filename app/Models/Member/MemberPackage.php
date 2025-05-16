<?php

namespace App\Models\Member;

use App\Models\User;
use App\Traits\HasFormatRupiah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberPackage extends Model
{
    use HasFormatRupiah;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'package_name',
        'days',
        'package_type_id',
        'package_category_id',
        'package_price',
        'admin_price',
        'description',
        'user_id'
    ];

    protected $hidden = [];
    // public $timestamps = true;

    public function memberPackageType()
    {
        return $this->belongsTo(MemberPackageType::class, 'package_type_id', 'id');
    }

    public function memberPackageCategories()
    {
        return $this->belongsTo(MemberPackageCategory::class, 'package_category_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
