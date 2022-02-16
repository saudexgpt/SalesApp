<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerCall;
use App\Models\CustomerContact;
use App\Models\CustomerVerification;
use App\Models\SampleCustomer;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CustomersController extends Controller
{
    const ITEM_PER_PAGE = 10;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private function uploadPhoto($avatar)
    {
        $id = time();
        $upload_folder = 'uploads/customers';
        $path = "$upload_folder/" . $id . '.jpeg';
        $actualpath = "https://sales.3coretechnology.com/$path";
        // $actualpath = "http://localhost:8000/$path";
        file_put_contents($path, base64_decode($avatar));
        return $actualpath;
    }
    public function index(Request $request)
    {

        $user = $this->getUser();
        $searchParams = $request->all();
        $userQuery = Customer::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');
        if (!empty($keyword)) {
            $userQuery->where(function ($q) use ($keyword) {
                $q->where('business_name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('address', 'LIKE', '%' . $keyword . '%');
            });
        }
        $today  = date('Y-m-d', strtotime('now'));
        // $condition = [];
        // if (isset($request->customer_type_id)) {

        //     $customer_type_id = $request->customer_type_id;
        //     if ($customer_type_id != 'all') {
        //         $condition = ['customer_type_id' => $customer_type_id];
        //     }
        // }
        $condition = [];
        if (isset($request->customer_type_id)) {

            $customer_type_id = $request->customer_type_id;
            if ($customer_type_id != 'all') {
                $condition = array_merge($condition, ['customer_type_id' => $customer_type_id]);
            }
        }
        if ($user->hasRole('sales_rep')) {
            $customers = $userQuery->Confirmed()->with([
                'customerContacts',
                'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

                'visits' => function ($q) {
                    $q->orderBy('id', 'DESC')->paginate(10);
                },

            ])->where($condition)->where('relating_officer', $user->id)->orderBy('id', 'DESC')->paginate($limit);
            return response()->json(compact('customers'), 200);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $customers = $userQuery->Confirmed()->with([
                'customerContacts',
                'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

                'visits' => function ($q) {
                    $q->orderBy('id', 'DESC')->paginate(10);
                },

            ])->where($condition)->whereIn('relating_officer', $sales_reps_ids)->orderBy('id', 'DESC')->paginate($limit);
            return response()->json(compact('customers'), 200);
        } else {
            // admin and super admin only
            $customers = $userQuery->Confirmed()->with([
                'customerContacts',
                'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

                'visits' => function ($q) {
                    $q->orderBy('id', 'DESC')->paginate(10);
                },

            ])->where($condition)->orderBy('id', 'DESC')->paginate($limit);
            return response()->json(compact('customers'), 200);
        }
    }

    public function prospectiveCustomers(Request $request)
    {
        $user = $this->getUser();
        $searchParams = $request->all();
        $userQuery = Customer::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');
        if (!empty($keyword)) {
            $userQuery->where(function ($q) use ($keyword) {
                $q->where('business_name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('address', 'LIKE', '%' . $keyword . '%');
            });
        }
        $today  = date('Y-m-d', strtotime('now'));
        // $condition = [];
        // if (isset($request->customer_type_id)) {

        //     $customer_type_id = $request->customer_type_id;
        //     if ($customer_type_id != 'all') {
        //         $condition = ['customer_type_id' => $customer_type_id];
        //     }
        // }
        $condition = [];
        if (isset($request->customer_type_id)) {

            $customer_type_id = $request->customer_type_id;
            if ($customer_type_id != 'all') {
                $condition = array_merge($condition, ['customer_type_id' => $customer_type_id]);
            }
        }
        if ($user->hasRole('sales_rep')) {
            $customers = $userQuery->Prospective()->with([
                'customerContacts',
                'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

                'visits' => function ($q) {
                    $q->orderBy('id', 'DESC')->paginate(10);
                },

            ])->where($condition)->where('relating_officer', $user->id)->orderBy('id', 'DESC')->paginate($limit);
            return response()->json(compact('customers'), 200);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $customers = $userQuery->Prospective()->with([
                'customerContacts',
                'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

                'visits' => function ($q) {
                    $q->orderBy('id', 'DESC')->paginate(10);
                },

            ])->where($condition)->whereIn('relating_officer', $sales_reps_ids)->orderBy('id', 'DESC')->paginate($limit);
            return response()->json(compact('customers'), 200);
        } else {
            // admin and super admin only
            $customers = $userQuery->Prospective()->with([
                'customerContacts',
                'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

                'visits' => function ($q) {
                    $q->orderBy('id', 'DESC')->paginate(10);
                },

            ])->where($condition)->orderBy('id', 'DESC')->paginate($limit);
            return response()->json(compact('customers'), 200);
        }
    }
    public function sampleCustomers(Request $request)
    {
        $customers = SampleCustomer::get();

        return response()->json(compact('customers'), 200);
    }
    public function updateSampleCustomer(Request $request, SampleCustomer $sample_customer)
    {
        $sample_customer->address = $request->address;
        $sample_customer->save();
        return response()->json([], 204);
    }

    public function all()
    {
        $user = $this->getUser();
        if ($user->hasRole('sales_rep')) {
            $customers = Customer::with('customerContacts')->where(['relating_officer' => $user->id])->get();
            return response()->json(compact('customers'), 200);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $customers = Customer::with('customerContacts')->whereIn('relating_officer', $sales_reps_ids)->get();
            return response()->json(compact('customers'), 200);
        } else {
            // admin and super admin only
            $customers = Customer::with('customerContacts')->get();
            return response()->json(compact('customers'), 200);
        }
    }

    // public function schedules(Customer $customer)
    // {
    //     $today = date('Y-m-d', strtotime('now'));
    //     $schedules = Schedule::where('customer_id', $customer->id)
    //         ->where(function ($q) use ($today) {
    //             $q->where('schedule_date', '>=', $today);
    //             $q->orWhere('repeat_schedule', 'yes');
    //         })
    //         ->orderBy('day_num')
    //         ->get()
    //         ->groupBy('day');
    //     return response()->json(compact('schedules'), 200);
    // }
    public function customerDetails(Customer $customer)
    {
        $today  = date('Y-m-d', strtotime('now'));
        $customer = $customer::with([
            'customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifications.verifier' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'visits' => function ($q) {
                $q->orderBy('id', 'DESC')->paginate(10);
            },
            'visits.details.contact',
            'visits.visitedBy',
            'payments.confirmer',
            'payments.transaction.staff',
            'transactions' => function ($q) {
                $q->orderBy('id', 'DESC')->paginate(10);
            },
            'transactions.details',
            'schedules' => function ($query) use ($today) {
                $query->where('schedule_date', '>=', $today)->orWhere('repeat_schedule', 'yes')->orderBy('day_num');
            }
        ])->find($customer->id);
        $schedules = Schedule::where('customer_id', $customer->id)
            ->where(function ($q) use ($today) {
                $q->where('schedule_date', '>=', $today);
                $q->orWhere('repeat_schedule', 'yes');
            })
            ->orderBy('day_num')
            ->get()
            ->groupBy('day');
        return response()->json(compact('schedules', 'customer'), 200);
    }
    public function myCustomers(Request $request)
    {
        //
        $today  = date('Y-m-d', strtotime('now'));
        $user = $this->getUser();
        $condition = ['relating_officer' => $user->id];
        if (isset($request->customer_type_id)) {

            $customer_type_id = $request->customer_type_id;
            if ($customer_type_id != 'all') {
                $condition = ['relating_officer' => $user->id, 'customer_type_id' => $customer_type_id];
            }
        }
        $customers = Customer::with([
            'customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier',
            'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'visits' => function ($q) use ($user) {
                $q->where('visitor', $user->id)->orderBy('id', 'DESC');
            },
            'visits.details.contact',
            'payments.confirmer', 'payments.transaction.staff', 'transactions',
            'schedules' => function ($query) use ($user, $today) {
                $query->where('schedule_date', '>=', $today)->orWhere('repeat_schedule', 'yes')->where('rep', $user->id)->orderBy('day_num');
            }
        ])->where($condition)->orderBy('id', 'DESC')->paginate(10);
        return response()->json(compact('customers'), 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $this->getUser();
        $unsaved_customers = json_decode(json_encode($request->unsaved_customers));
        $customer_list = [];
        $unsaved_list = [];
        foreach ($unsaved_customers as $unsaved_customer) {
            $customer = Customer::where('business_name', $unsaved_customer->business_name)->first();


            if (!$customer) {

                $lat = $unsaved_customer->customer_latitude;
                $long = $unsaved_customer->customer_longitude;
                $reg_lat = $unsaved_customer->registrar_lat;
                $reg_lng = $unsaved_customer->registrar_lng;
                $formatted_address = $unsaved_customer->address;
                $street = $unsaved_customer->street;
                $area = $unsaved_customer->area;
                try {

                    // we fetch the geo information of the given address
                    if ($lat == '' || $long == '' ||  $area == '') {
                        list($lat, $long, $formatted_address, $street, $area) = $this->getLocationFromAddress($formatted_address);
                    }
                    $contacts = json_decode(json_encode($unsaved_customer->customer_contacts));
                    $customer = new Customer();
                    $customer->customer_type_id = $unsaved_customer->customer_type_id;
                    $customer->tier_id = $unsaved_customer->tier_id;
                    $customer->lga_id = $unsaved_customer->sub_region_id;
                    $customer->state_id = $unsaved_customer->region_id;
                    $customer->business_name = $unsaved_customer->business_name;
                    $customer->email = $unsaved_customer->email;
                    // $customer->phone1 = $unsaved_customer->phone1;
                    $customer->photo = $this->uploadPhoto($unsaved_customer->avatar);
                    $customer->base64_encoded_image = $unsaved_customer->avatar;
                    $customer->address = $formatted_address;
                    $customer->street = $street;
                    $customer->area = $area;
                    $customer->longitude = $long;
                    $customer->latitude = $lat;
                    $customer->registered_by = $user->id;
                    $customer->registrar_lat = $reg_lat;
                    $customer->registrar_lng = $reg_lng;
                    $customer->relating_officer = $user->id;
                    if ($customer->save()) {
                        if (count($contacts) > 0) {
                            $this->saveCustomerContact($customer->id, $contacts);
                        }

                        $customer_list[] = $this->show($customer);

                        $title = "New Customer Added";
                        $description = "New customer, $customer->business_name, was added by " . $user->id;
                        $this->logUserActivity($title, $description, $user);
                    }
                    // Generate notification before returning ///////////////////////
                    // Write notification code here////////////////////////////

                } catch (\Throwable $th) {
                    $unsaved_list[] = $unsaved_customer;
                }
            }
        }
        return response()->json(['customers' => $customer_list, 'unsaved_list' => $unsaved_list, 'message' => 'success'], 200);
    }
    private function saveCustomerContact($customer_id, $contacts)
    {
        foreach ($contacts as $contact) {
            $new_contact = new CustomerContact();
            $new_contact->customer_id = $customer_id;
            $new_contact->name = $contact->name;
            $new_contact->phone1 = $contact->phone1;
            $new_contact->phone2 = $contact->phone2;
            $new_contact->role = $contact->role;
            $new_contact->save();
        }
    }
    public function addCustomerContact(Request $request)
    {
        $user = $this->getUser();
        $customer_id = $request->customer_id;
        $customer = Customer::find($customer_id);
        $contacts = json_decode(json_encode($request->customer_contacts));
        if (count($contacts) > 0) {

            // delete old
            $customer->customerContacts->delete();
            $this->saveCustomerContact($customer_id, $contacts);

            $title = "Customer Contacts Added";
            $description = "Contacts added for $customer->business_name";
            $this->logUserActivity($title, $description, $user);
        }
        $contacts = CustomerContact::where('customer_id', $customer_id)->get();
        return $contacts;
    }
    public function getLatLongLocation(Request $request)
    {
        $lat = $request->latitude;
        $long = $request->longitude;
        return $this->getLocationFromLatLong($lat, $long);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        $user = $this->getUser();
        $today  = date('Y-m-d', strtotime('now'));
        $customer = $customer::with([
            'customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier',
            'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'visits' => function ($q) use ($user) {
                $q->where('visitor', $user->id)->orderBy('id', 'DESC');
            },
            'visits.details.contact',
            'payments.confirmer', 'payments.transaction.staff', 'transactions',
            'schedules' => function ($query) use ($user, $today) {
                $query->where('schedule_date', '>=', $today)->orWhere('repeat_schedule', 'yes')->where('rep', $user->id)->orderBy('day_num');
            }
        ])->find($customer->id);
        return $customer;
    }
    public function verify(Customer $customer)
    {
        $user = $this->getUser();
        $customer_verification = new CustomerVerification();
        $today  = date('Y-m-d', strtotime('now'));
        $customer_verification->verified_by = $user->id;
        $customer_verification->customer_id = $customer->id;
        $customer_verification->date = $today;
        $customer_verification->save();
        $title = "Customer Verified Successfully";
        $description = $user->name . " successfully verified $customer->business_name on $today";
        $this->logUserActivity($title, $description, $user);
        return $this->customerDetails($customer);
    }


    public function saveCustomerCalls(Request $request)
    {
        $user = $this->getUser();
        $calls_made = json_decode(json_encode($request->calls_made));
        foreach ($calls_made as $call) {

            $duration_in_seconds = $call->duration;
            if ($duration_in_seconds > 0) {

                $phone_number = $call->number;
                $date = date('Y-m-d', $call->date / 1000); // we change $call->date to seconds from miliseconds
                $customer_call = CustomerCall::where([
                    'phone_no' => $phone_number,
                    'date' => $date,
                    'duration_in_seconds' => $duration_in_seconds,
                    'caller' => $user->id
                ])->first();
                if (!$customer_call) {
                    // add customer call
                    $customer_contact = CustomerContact::where('phone1', $phone_number)
                        ->orWhere('phone2', $phone_number)
                        ->first();
                    $customer_id = ($customer_contact) ? $customer_contact->customer_id : null;
                    $customer_call = new CustomerCall();
                    $customer_call->customer_id = $customer_id;
                    $customer_call->phone_no = $phone_number;
                    $customer_call->caller = $user->id;
                    $customer_call->date = $date;
                    $customer_call->duration_in_seconds = $duration_in_seconds;
                    $customer_call->save();
                }
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function confirmCustomer(Request $request, Customer $customer)
    {
        $user = $this->getUser();
        $today  = date('Y-m-d', strtotime('now'));
        $customer->status = 'Confirmed';
        $customer->save();

        $title = "Customer Confirmation Successful";
        $description = $user->name . " successfully confirmed $customer->business_name on $today";
        $this->logUserActivity($title, $description, $user);

        // return $this->prospectiveCustomers($request);
    }

    public function assignFieldStaff(Request $request, Customer $customer)
    {
        $staff_id = $request->staff_id;
        $relating_officer = User::find($staff_id);
        $user = $this->getUser();
        $today  = date('Y-m-d', strtotime('now'));
        $customer->relating_officer = $staff_id;
        $customer->save();

        $title = "Customer's Relating Officer Assigned";
        $description = $user->name . " successfully assigned $relating_officer->name to $customer->business_name on $today";
        $this->logUserActivity($title, $description, $relating_officer);

        // return $this->prospectiveCustomers($request);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
