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
use App\Models\Payment;
use App\Models\ReturnedProduct;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\State;
use App\Models\SubInventory;
use App\Models\TeamMember;
use App\Models\TeamProduct;
use App\Models\Transaction;
use App\Models\TransactionFile;
use App\Models\User;
use App\Notifications\AuditTrail;
use Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    protected $user;
    public function getSkippedInvoiceNumbers(Request $request)
    {
        $lower_limit = $request->lower_limit;
        $used = $request->used;
        $used_array = explode('~', $used);
        sort($used_array);
        $next_no = $lower_limit;
        $skipped_nos = [];
        foreach ($used_array as $number) {
            if ($number > $next_no) {
                $difference = $number - $next_no;

                for ($i = 1; $i <= $difference; $i++) {
                    $skipped_nos[] = $number - $i;
                }

                $next_no = $number + 1;
            } else {

                $next_no++;
            }
        }
        sort($skipped_nos);
        return $skipped_nos;
    }

    public function addTeamProducts($team_id, $item_id)
    {
        $team_product = TeamProduct::where(['team_id' => $team_id, 'item_id' => $item_id])->first();
        if (!$team_product) {
            $team_product = new TeamProduct();
            $team_product->team_id = $team_id;
            $team_product->item_id = $item_id;
            $team_product->save();
        }
    }
    // public function uploadFile($request)
    // {
    //     if ($request->file('avatar') != null && $request->file('avatar')->isValid()) {
    //         $mime = $request->file('avatar')->getClientMimeType();

    //         if ($mime == 'image/png' || $mime == 'image/jpeg' || $mime == 'image/jpg' || $mime == 'image/gif') {
    //             $name = time() . "." . $request->file('avatar')->guessClientExtension();
    //             $folder = "customers";
    //             $avatar = $request->file('avatar')->storeAs($folder, $name, 'public');

    //             return response()->json(['avatar' => 'storage/' . $avatar], 200);
    //         }
    //     }
    // }

    public function uploadFile($media, $file_name, $folder)
    {
        $photo = $media->storeAs($folder, $file_name, 'public');

        return $folder . '/' . $file_name;
    }

    public function attachFiles(Request $request)
    {
        // if ($request->hasFile('files')) {
        $media = $request->file('files');
        $type = $request->type;
        // foreach ($files as $media) {

        if ($media != null && $media->isValid()) {
            $file_name = time() . "." . $media->guessClientExtension();
            $folder = "transactions/" . $type;
            return  $this->uploadFile($media, $file_name, $folder);
        }
        // }
        // }
    }
    public function saveTransactionFile($tnx_id, $type, $link)
    {
        $tnx_file = new TransactionFile();
        $tnx_file->tnx_id = $tnx_id;
        $tnx_file->tnx_type = $type;
        $tnx_file->link = $link;
        $tnx_file->save();
    }

    public function setUser()
    {
        $this->user  = User::find(Auth::user()->id); // new UserResource(Auth::user());
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
    public function teamMembers($team_id = null)
    {
        $user = $this->getUser();
        if ($user->isManager()) {
            return $this->managerTeamMembers();
        }
        $all_team_members = [];
        $all_team_member_ids = [];
        $team_member = $user->memberOfTeam;
        // foreach ($team_members as $team_member) {
        if ($team_id == null) {

            $team_id = $team_member->team_id;
        }
        $my_members = TeamMember::where('team_id', $team_id)->where('user_id', '!=', $user->id)->get();
        foreach ($my_members as $my_member) {
            $all_team_members[] = $my_member->user;
            $all_team_member_ids[] = $my_member->user->id;
        }
        // }
        return array($all_team_members, $all_team_member_ids);
    }
    public function allTeamMembers($team_id = null)
    {
        $all_team_members = [];
        $all_team_member_ids = [];
        if ($team_id == null) {

            $my_members = TeamMember::get();
        } else {

            $my_members = TeamMember::where('team_id', $team_id)->get();
        }
        foreach ($my_members as $my_member) {
            $all_team_members[] = $my_member->user;
            $all_team_member_ids[] = $my_member->user->id;
        }
        // }
        return array($all_team_members, $all_team_member_ids);
    }
    public function getInvoiceNo($prefix, $next_no, $no_of_digits = 7)
    {

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
        $specialties = ['Cardiologist', 'Dentist', 'Dermatologist', 'Endocrinologist', 'Pharmacist', 'Gynaecologist & Obstetrician', 'Optometrist', 'Neorologist', 'Surgeon', 'Pediatrician', 'Nurse', 'Mid-Wife', 'MD/CEO', 'Manager', 'General Practitioner'];

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

    public function setQueryConditions($data, $rep_field_name = null)
    {
        $condition = [];
        if ($rep_field_name) {
            if (isset($data->rep_id) && $data->rep_id != '' && $data->rep_id != 'all') {

                $condition = array_merge($condition, [$rep_field_name => $data->rep_id]);
            }
        }

        if (isset($data->customer_id) && $data->customer_id != '' && $data->customer_id != 'all') {
            $condition = array_merge($condition, ['customer_id' => $data->customer_id]);
        }
        if (isset($data->customer_type_id) && $data->customer_type_id != '' && $data->customer_type_id != 'all') {

            $condition = array_merge($condition, ['customer_type_id' => $data->customer_type_id]);
        }
        return $condition;
    }
    public function logComplaints(Request $request, $id)
    {
        $type = $request->type;
        $complaints = $request->complaints;

        switch ($type) {
            case 'sales':
                $transaction = Transaction::find($id);
                $transaction->complaints = $complaints;
                $transaction->complain_status = 'pending';
                $transaction->save();
                break;

            case 'collections':
                $payment = Payment::find($id);
                $payment->complaints = $complaints;
                $payment->complain_status = 'pending';
                $payment->save();
                break;
            case 'returns':
                $returns = ReturnedProduct::find($id);
                $returns->complaints = $complaints;
                $returns->complain_status = 'pending';
                $returns->save();
                break;
        }
    }

    public function sendFirebaseMessage($title, $body, $user = null)
    {
        $firebaseToken = User::whereNotNull('device_token')->pluck('device_token')->all();

        if (empty($firebaseToken)) {
            return 'No valid user with device token';
        }

        $SERVER_API_KEY = env('FCM_SERVER_KEY');



        $data = [

            "registration_ids" => $firebaseToken,

            "notification" => [

                "title" => $title,

                "body" => $body,

            ]

        ];

        $dataString = json_encode($data);



        $headers = [

            'Authorization: key=' . $SERVER_API_KEY,

            'Content-Type: application/json',

        ];



        $ch = curl_init();



        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);



        $response = curl_exec($ch);



        return response()->json(compact('response'), 200);
    }
}
