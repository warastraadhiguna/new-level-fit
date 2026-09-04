<?php

namespace App\Models\Pos;

use App\Models\BranchStore;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'pos_sales';
    protected $guarded = [];
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class, 'sale_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class, 'branch_store_id');
    }
}
