<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'field_staff', 'id');
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id', 'id');
    }
    public function attachments()
    {
        return $this->hasMany(TransactionFile::class, 'tnx_id', 'id');
    }
}
