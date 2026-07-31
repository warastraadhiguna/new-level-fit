<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'pos_products';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function branchProducts()
    {
        return $this->hasMany(BranchProduct::class, 'product_id');
    }
}
