<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDebt extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function sale()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'debt_id', 'id');
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'field_staff', 'id');
    }
}
