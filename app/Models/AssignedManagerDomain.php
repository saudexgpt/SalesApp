<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedManagerDomain extends Model
{
    use HasFactory;
    public function managerType()
    {
        return $this->belongsTo(ManagerType::class, 'manager_type_id', 'id');
    }
    public function managerDomain()
    {
        return $this->belongsTo(ManagerDomain::class, 'manager_domain_id', 'id');
    }
}
