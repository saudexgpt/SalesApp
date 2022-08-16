<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitDetailedProduct extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'product_id', 'id');
    }
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
