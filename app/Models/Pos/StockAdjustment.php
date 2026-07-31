<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table = 'pos_stock_adjustments';
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }
}
