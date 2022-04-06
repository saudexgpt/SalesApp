<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerType extends Model
{
    use HasFactory;
    public function downlinks()
    {
        return $this->hasMany(ManagerDomain::class, 'report_to', 'slug');
    }
    // public function assignedManagerDomain()
    // {
    //     return $this->hasMany(AssignedManagerDomain::class, 'manager_type_id', 'id');
    // }
}
