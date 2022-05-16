<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStockBalance extends Model
{
    use HasFactory;
    public function item()
    {
        return $this->belongsTo(Item::class, 'product_id', 'id');
    }
}
