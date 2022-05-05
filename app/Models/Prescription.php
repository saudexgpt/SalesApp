<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;
    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
