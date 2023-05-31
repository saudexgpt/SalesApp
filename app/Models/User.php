<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use LaratrustUserTrait;
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'username',
        'phone',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function memberOfTeam()
    {
        return $this->hasOne(TeamMember::class);
    }
    public function memberOfTeams()
    {
        return $this->hasMany(TeamMember::class);
    }
    // public function customers()
    // {
    //     return $this->hasMany(Customer::class, 'relating_officer', 'id');
    // }
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_user', 'user_id', 'customer_id');
    }
    public function subInventories()
    {
        return $this->hasMany(SubInventory::class, 'staff_id', 'id');
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'field_staff', 'id');
    }
    public function debts()
    {
        return $this->hasMany(CustomerDebt::class, 'field_staff', 'id');
    }
    public function customerPayments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Customer::class,
            'relating_officer', // Foreign key on the customers table...
            'customer_id', // Foreign key on the payments table...
            'id', // Local key on the users table...
            'id' // Local key on the customer table...
        );
    }
    public function visits()
    {
        return $this->hasMany(Visit::class, 'visitor', 'id');
    }
    public function geolocations()
    {
        return $this->hasMany(UserGeolocation::class, 'user_id', 'id');
    }
    public function mySchedules()
    {
        return $this->hasMany(Schedule::class, 'rep', 'id');
    }
    public function managerDomain()
    {
        return $this->hasOne(ManagerDomain::class, 'user_id', 'id');
    }

    // public function assignedManagerDomains()
    // {
    //     return $this->hasMany(AssignedManagerDomain::class, 'user_id', 'id');
    // }
    public function isSuperAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->isSuperAdmin()) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->isAdmin()) {
                return true;
            }
        }

        return false;
    }

    public function isManager(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->isManager()) {
                return true;
            }
        }

        return false;
    }
}
