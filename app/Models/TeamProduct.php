<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamProduct extends Model
{
    use HasFactory;
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
