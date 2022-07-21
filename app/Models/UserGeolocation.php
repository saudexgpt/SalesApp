<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGeolocation extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function updateLocation($user_id, $lat, $long, $accuracy = NULL)
    {
        $today = todayDate();
        $location = UserGeolocation::where('user_id', $user_id)
            ->where('created_at', 'LIKE', $today . '%')->first();
        // ->where(['longitude' => $longitude, 'latitude' => $latitude])->first();
        if (!$location) {
            $location = new UserGeolocation();
        }
        $location->user_id = $user_id;
        $location->longitude = $long;
        $location->latitude = $lat;
        $location->accuracy = $accuracy;
        $location->save();
        return 'success';
        // $locations = UserGeolocation::where('user_id', $user->id)
        //     ->where('created_at', 'LIKE', $today . '%')->get();
        // return response()->json(compact('locations'), 200);
    }
}
