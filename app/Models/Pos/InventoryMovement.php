<?php

namespace App\Models\Pos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $table = 'pos_inventory_movements';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
