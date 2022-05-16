<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use App\Models\Customer;
use App\Models\LocalGovernmentArea;
use App\Models\ManagerDomain;
use App\Models\Role;
use App\Models\State;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\AuditTrail;
use Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $user;
    public function uploadFile($request)
    {
        if ($request->file('avatar') != null && $request->file('avatar')->isValid()) {
            $mime = $request->file('avatar')->getClientMimeType();

            if ($mime == 'image/png' || $mime == 'image/jpeg' || $mime == 'image/jpg' || $mime == 'image/gif') {
                $name = time() . "." . $request->file('avatar')->guessClientExtension();
                $folder = "customers";
                $avatar = $request->file('avatar')->storeAs($folder, $name, 'public');

                return response()->json(['avatar' => 'storage/' . $avatar], 200);
            }
        }
    }

    public function setUser()
    {
        $this->user  = new UserResource(Auth::user());
    }

    public function getUser()
    {
        $this->setUser();

        return $this->user;
    }
    public function currency()
    {
        return '₦';
    }
    public function managerTeamMembers()
    {
        $user = $this->getUser();

        $all_team_members = [];
        $all_team_member_ids = [];
        $manager_domain = ManagerDomain::where('user_id', $user->id)->first();
        if ($manager_domain) {
            $reps_ids = $manager_domain->reps_ids;
            $all_team_member_ids = explode('~', $reps_ids);
            $all_team_members = User::whereIn('id', $all_team_member_ids)->get();
        }
        return array($all_team_members, $all_team_member_ids);
    }
    // public function managersDomain()
    // {
    //     $user = $this->getUser();

    //     $field = '';
    //     $domain_ids_array = [];
    //     $manager_domain = ManagerDomain::where('user_id', $user->id)->first();
    //     if ($manager_domain) {
    //         $domain_ids = $manager_domain->reps_ids;
    //         $domain_ids_array = explode('~', $domain_ids);
    //         $domain = $manager_domain->domain;
    //         $field = $domain . '_id'; // i.e country_id, state_id, lga_id
    //         // $customers = Customer::whereIn($field, $domain_ids_array)->get();
    //         // foreach ($variable as $key => $value) {
    //         //     # code...
    //         // }
    //     }
    //     return array($field, $domain_ids_array);
    // }
    public function teamMembers()
    {
        $user = $this->getUser();
        if ($user->isManager()) {
            return $this->managerTeamMembers();
        }
        $all_team_members = [];
        $all_team_member_ids = [];
        $team_member = $user->memberOfTeam;
        // foreach ($team_members as $team_member) {
        $team_id = $team_member->team_id;
        $my_members = TeamMember::with('user.customers')->where('team_id', $team_id)->where('user_id', '!=', $user->id)->get();
        foreach ($my_members as $my_member) {
            $all_team_members[] = $my_member->user;
            $all_team_member_ids[] = $my_member->user->id;
        }
        // }
        return array($all_team_members, $all_team_member_ids);
    }

    public function getLocationFromLatLong($lat, $long)
    {

        // echo urlencode($address);
        $apiKey = env('GOOGLE_API_KEY');
        $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $long . '&key=' . $apiKey);

        // $json = json_decode($json);
        // print_r($json);
        return $json;
        // $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        // $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
        // $formatted_address = $json->{'results'}[0]->{'formatted_address'};
        // $street = '';
        // $area = '';
        // $address_components = $json->{'results'}[0]->{'address_components'};
        // foreach ($address_components as $address_component) {
        //     $types = $address_component->types;
        //     foreach ($types as $key => $value) {
        //         if ($value === 'route') {
        //             $street = $address_component->long_name;
        //         }
        //         if ($value === 'administrative_area_level_2') {
        //             $area = $address_component->long_name;
        //         } else if ($value === 'locality') {
        //             $area = $address_component->long_name;
        //         }
        //     }
        // }
        // // $formatted_address = $json->{'results'}[0]->{'formatted_address'};
        // // $street = $json->{'results'}[0]->{'address_components'}[1]->{'long_name'};
        // // $area = $json->{'results'}[0]->{'address_components'}[4]->{'long_name'};
        // return array($lat, $long, $formatted_address, $street, $area);
    }
    public function getLocationFromAddress($address)
    {
        $address = str_replace(',', '', $address);
        // echo urlencode($address);
        $apiKey = env('GOOGLE_API_KEY');
        $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey);

        $json = json_decode($json);
        // print_r($json);
        // return $json;
        $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
        $formatted_address = $json->{'results'}[0]->{'formatted_address'};
        $street = '';
        $area = '';
        $address_components = $json->{'results'}[0]->{'address_components'};
        foreach ($address_components as $address_component) {
            $types = $address_component->types;
            foreach ($types as $key => $value) {
                if ($value === 'route') {
                    $street = $address_component->long_name;
                }
                if ($value === 'administrative_area_level_2') {
                    $area = $address_component->long_name;
                } else if ($value === 'locality') {
                    $area = $address_component->long_name;
                }
            }
        }
        // $formatted_address = $json->{'results'}[0]->{'formatted_address'};
        // $street = $json->{'results'}[0]->{'address_components'}[1]->{'long_name'};
        // $area = $json->{'results'}[0]->{'address_components'}[4]->{'long_name'};
        return array($lat, $long, $formatted_address, $street, $area);
    }
    public function getInvoiceNo($prefix, $next_no)
    {
        $no_of_digits = 5;

        $digit_of_next_no = strlen($next_no);
        $unused_digit = $no_of_digits - $digit_of_next_no;
        $zeros = '';
        for ($i = 1; $i <= $unused_digit; $i++) {
            $zeros .= '0';
        }

        return $prefix . $zeros . $next_no;
    }
    public function fetchNecessayParams()
    {
        $user = $this->getUser();
        $all_roles = Role::orderBy('name')->get();
        $default_roles = Role::orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $lgas = LocalGovernmentArea::with('state')->orderBy('name')->get();
        return response()->json([
            'params' => compact('all_roles', 'default_roles', 'states', 'lgas')
        ]);
    }
    public function contactSpecialties()
    {
        $specialties = ['Cardiologist', 'Dentist', 'Dermatologist', 'Endocrinologist', 'Pharmacist', 'Gynaecologist and Obstetrician', 'Optometrist', 'Neorologist', 'Surgeon', 'Pediatrician', 'Nurse', 'Mid-Wife'];

        return response()->json(compact('specialties'), 200);
    }
    public function logUserActivity($title, $description, $user = null)
    {
        // $user = $this->getUser();
        // if ($role) {
        //     $role->notify(new AuditTrail($title, $description));
        // }
        // return $user->notify(new AuditTrail($title, $description));
        // send notification to admin at all times
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', '=', 'super')
                ->orWhere('name', '=', 'admin'); // this is the role id inside of this callback
        })->get();

        // if ($user != null) {
        //     $users = $users->push($user);
        // }
        // $others = User::whereHas('roles', function ($query) use ($roles) {
        //     $query->whereIn('name', $roles); // this is the role id inside of this callback
        // })->get();
        // $users = $users->merge($others);
        // var_dump($users);
        $notification = new AuditTrail($title, $description);
        return Notification::send($users->unique(), $notification);
        // $activity_log = new ActivityLog();
        // $activity_log->user_id = $user->id;
        // $activity_log->action = $action;
        // $activity_log->user_type = $user->roles[0]->name;
        // $activity_log->save();
    }
}
