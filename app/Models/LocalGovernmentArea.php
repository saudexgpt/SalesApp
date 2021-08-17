<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalGovernmentArea extends Model
{
    use HasFactory;
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function customers()
    {
        return $this->hasMany(Customer::class, 'lga_id', 'id');
    }
}
