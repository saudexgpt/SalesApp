<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    public function lgas()
    {
        return $this->hasMany(LocalGovernmentArea::class);
    }
}
