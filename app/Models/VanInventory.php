<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VanInventory extends Model
{
    use HasFactory;
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
    public function mainInventory()
    {
        return $this->belongsTo(SubInventory::class, 'sub_inventory_id', 'id');
    }
}
