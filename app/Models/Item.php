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
}
