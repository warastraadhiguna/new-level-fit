<?php

namespace App\Models\Staff;

use App\Models\BranchStore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalTrainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_store_id',
        'full_name',
        'gender',
        'role',
        'phone_number',
        'address',
        'description',
        'user_id'
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class);
    }
}
