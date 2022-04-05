<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerType extends Model
{
    use HasFactory;
    public function managerDomains()
    {
        return $this->hasMany(ManagerDomain::class, 'manager_type_id', 'id');
    }
    // public function assignedManagerDomain()
    // {
    //     return $this->hasMany(AssignedManagerDomain::class, 'manager_type_id', 'id');
    // }
}
