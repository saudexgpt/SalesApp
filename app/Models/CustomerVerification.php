<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerVerification extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }
}
