<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function visitedBy()
    {
        return $this->belongsTo(User::class, 'visitor', 'id');
    }
    public function details()
    {
        return $this->hasMany(VisitDetail::class, 'visit_id', 'id');
    }
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'visit_id', 'id');
    }
    public function contact()
    {
        return $this->belongsTo(CustomerContact::class, 'customer_contact_id', 'id');
    }
}
