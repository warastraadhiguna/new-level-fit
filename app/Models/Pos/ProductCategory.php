<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'pos_product_categories';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
