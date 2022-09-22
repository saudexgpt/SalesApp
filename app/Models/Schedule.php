<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    public function scheduledBy()
    {
        return $this->belongsTo(User::class, 'scheduled_by', 'id');
    }
    public function rep()
    {
        return $this->belongsTo(User::class, 'rep', 'id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
