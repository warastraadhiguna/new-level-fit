<?php

namespace App\Models\Pos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'pos_purchases';
    protected $guarded = [];
    protected $casts = [
        'purchase_date' => 'date',
        'received_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
