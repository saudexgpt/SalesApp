<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnedProduct extends Model
{
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function rep()
    {
        return $this->belongsTo(User::class, 'stocked_by', 'id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
