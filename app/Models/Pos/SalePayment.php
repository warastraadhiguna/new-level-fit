<?php

namespace App\Models\Pos;

use App\Models\MethodPayment;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $table = 'pos_sale_payments';
    protected $guarded = [];

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class);
    }
}
