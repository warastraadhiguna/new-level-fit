<?php

namespace App\Models\Pos;

use App\Models\BranchStore;
use Illuminate\Database\Eloquent\Model;

class BranchProduct extends Model
{
    protected $table = 'pos_branch_products';
    protected $guarded = [];
    protected $casts = [
        'stock_qty' => 'decimal:3',
        'average_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'minimum_stock' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class);
    }
}
