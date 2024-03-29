<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerDomain extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function cordinator()
    {
        return $this->belongsTo(ManagerType::class, 'report_to', 'slug');
    }
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
    // public function assignedManagerDomain()
    // {
    //     return $this->hasMany(AssignedManagerDomain::class, 'manager_domain_id', 'id');
    // }
    // public function country()
    // {
    //     return $this->belongsTo(Country::class, 'country_id', 'id');
    // }
    // public function state()
    // {
    //     return $this->belongsTo(State::class, 'state_id', 'id');
    // }

    // public function lga()
    // {
    //     return $this->belongsTo(LocalGovernmentArea::class, 'lga_id', 'id');
    // }
}
