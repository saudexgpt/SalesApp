<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitDetail extends Model
{
    use HasFactory;
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
    public function contact()
    {
        return $this->belongsTo(CustomerContact::class, 'customer_contact_id', 'id');
    }
}
