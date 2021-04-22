<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'customer_type_id',
        'tier_id',
        'sub_region_id',
        'region_id',
        'business_name',
        'email',
        'phone1',
        'phone2',
        'address',
        'street',
        'area',
        'longitude',
        'latitude',
        'registered_by',
        'relating_officer',
    ];
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }
    public function tier()
    {
        return $this->belongsTo(Tier::class);
    }
    public function subRegion()
    {
        return $this->belongsTo(SubRegion::class);
    }
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    public function registrar()
    {
        return $this->belongsTo(User::class, 'registered_by', 'id');
    }
    public function assignedOfficer()
    {
        return $this->belongsTo(User::class, 'relating_officer', 'id');
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
