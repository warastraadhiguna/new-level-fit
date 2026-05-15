<?php

namespace App\Models\Trainer;

use App\Models\BranchStore;
use App\Models\User;
use App\Traits\HasFormatRupiah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerPackage extends Model
{
    use HasFormatRupiah;
    use HasFactory;

    protected $fillable = [
        'branch_store_id',        
        'package_name',
        'number_of_session',
        'days',
        'package_price',
        'admin_price',
        'description',
        'user_id',
        'status'
    ];

    protected $hidden = [];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->withTrashed()
            ->withDefault([
                'full_name' => '-',
            ]);
    }

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class)
            ->withDefault([
                'name' => '-',
            ]);
    }        
}
