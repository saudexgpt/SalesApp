<?php

namespace App\Models;

use Laratrust\Models\LaratrustTeam;

class Team extends LaratrustTeam
{
    public $guarded = [];

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }
}
