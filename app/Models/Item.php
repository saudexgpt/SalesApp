<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use App\Models\Setting\Tax;

use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{

    use SoftDeletes;
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function price()
    {
        return $this->hasOne(ItemPrice::class);
    }
    public function inventories()
    {
        return $this->hasMany(SubInventory::class, 'item_id', 'id');
    }
    public function vanInventories()
    {
        return $this->hasMany(VanInventory::class, 'item_id', 'id');
    }
}
