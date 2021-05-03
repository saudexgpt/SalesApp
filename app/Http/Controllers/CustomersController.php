<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $user = $this->getUser();
        $condition = ['relating_officer' => $user->id];
        if (isset($request->customer_type_id)) {

            $customer_type_id = $request->customer_type_id;
            if ($customer_type_id != 'all') {
                $condition = ['relating_officer' => $user->id, 'customer_type_id' => $customer_type_id];
            }
        }
        $customer_type_id = $request->customer_type_id;
        $customers = Customer::with(['customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier', 'payments.confirmer', 'payments.transaction.staff' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'transactions'])->where($condition)->orderBy('id', 'DESC')->paginate(20);
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
        $unsaved_customers = json_decode(json_encode($request->unsaved_customers));
        $customer_list = [];
        foreach ($unsaved_customers as $unsaved_customer) {
            $user = $this->getUser();
            $customer = Customer::where('business_name', $unsaved_customer->business_name)->first();

            // we fetch the geo information of the given address
            list($lat, $long, $formatted_address, $street, $area) = $this->getLocation($unsaved_customer->address);
            if (!$customer) {
                $contacts = json_decode(json_encode($unsaved_customer->customer_contacts));
                $customer = new Customer();
                $customer->customer_type_id = $unsaved_customer->customer_type_id;
                $customer->tier_id = $unsaved_customer->tier_id;
                $customer->sub_region_id = $unsaved_customer->sub_region_id;
                $customer->region_id = $unsaved_customer->region_id;
                $customer->business_name = $unsaved_customer->business_name;
                $customer->email = $unsaved_customer->email;
                // $customer->phone1 = $unsaved_customer->phone1;
                // $customer->phone2 = $unsaved_customer->phone2;
                $customer->address = $formatted_address;
                $customer->street = $street;
                $customer->area = $area;
                $customer->longitude = $long;
                $customer->latitude = $lat;
                $customer->registered_by = $user->id;
                $customer->relating_officer = $user->id;
                if ($customer->save()) {
                    if (count($contacts) > 0) {
                        foreach ($contacts as $contact) {
                            $new_contact = new CustomerContact();
                            $new_contact->customer_id = $customer->id;
                            $new_contact->name = $contact->name;
                            $new_contact->phone1 = $contact->phone1;
                            $new_contact->phone2 = $contact->phone2;
                            $new_contact->role = $contact->role;
                            $new_contact->save();
                        }
                    }

                    $customer_list[] = $this->show($customer);
                }
                // Generate notification before returning ///////////////////////
                // Write notification code here////////////////////////////

            }
        }
        return response()->json(['customers' => $customer_list, 'message' => 'success'], 200);
    }

    private function getLocation($address)
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
        $customer = $customer::with(['customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier', 'payments.confirmer', 'payments.transaction.staff' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'transactions'])->find($customer->id);
        return $customer;
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
