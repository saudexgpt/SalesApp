<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function debtPayment()
    {
        return $this->hasMany(CustomerDebtPayment::class, 'payment_id', 'id');
    }
    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id');
    }
    public function attachments()
    {
        return $this->hasMany(TransactionFile::class, 'tnx_id', 'id');
    }
}
