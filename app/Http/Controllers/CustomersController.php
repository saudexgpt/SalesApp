<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerCall;
use App\Models\CustomerContact;
use App\Models\Schedule;
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
        $data = explode(',', $avatar);
        $actualpath = "https://sales.3coretechnology.com/$path";
        // $actualpath = "http://localhost:8000/$path";
        file_put_contents($path, base64_decode($data[1]));
        return $actualpath;
    }
    public function index(Request $request)
    {
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
        $condition = [];
        if (isset($request->customer_type_id)) {

            $customer_type_id = $request->customer_type_id;
            if ($customer_type_id != 'all') {
                $condition = ['customer_type_id' => $customer_type_id];
            }
        }

        $customers = $userQuery->with([
            'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer', 'verifier',

            'visits' => function ($q) {
                $q->orderBy('id', 'DESC')->paginate(10);
            },

        ])->where($condition)->orderBy('id', 'DESC')->paginate($limit);
        return response()->json(compact('customers'), 200);
    }
    public function all(Request $request)
    {
        $customers = Customer::get();
        return response()->json(compact('customers'), 200);
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
            'customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier',
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
                    // $customer->sub_region_id = $unsaved_customer->sub_region_id;
                    // $customer->region_id = $unsaved_customer->region_id;
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
        $customer_id = $request->customer_id;
        $contacts = json_decode(json_encode($request->customer_contacts));
        if (count($contacts) > 0) {
            $this->saveCustomerContact($customer_id, $contacts);
        }
        return 'success';
    }
    public function getLatLongLocation(Request $request)
    {
        $lat = $request->latitude;
        $long = $request->longitude;
        return $this->getLocationFromLatLong($lat, $long);
    }
    private function getLocationFromLatLong($lat, $long)
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
    private function getLocationFromAddress($address)
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
    public function edit(Customer $customer)
    {
        //
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
