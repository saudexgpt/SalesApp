<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDebtPayment extends Model
{
    use HasFactory, SoftDeletes;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function debt()
    {
        return $this->belongsTo(CustomerDebt::class, 'debt_id', 'id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }
}
