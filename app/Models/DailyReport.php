<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    public function customerReports()
    {
        return $this->hasMany(CustomerReport::class);
    }
    public function hospitalReports()
    {
        return $this->hasMany(HospitalReport::class);
    }
    public function reporter()
    {
        return $this->belongsTo(User::class, 'report_by', 'id');
    }
}
