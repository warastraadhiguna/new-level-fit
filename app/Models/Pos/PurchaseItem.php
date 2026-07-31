<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $table = 'pos_purchase_items';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
