<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $user = $this->getUser();
        $customers = Customer::with(['customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier', 'payments.confirmer', 'payments.transaction.staff' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'transactions'])->where('relating_officer', $user->id)->orderBy('id', 'DESC')->get();
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
        $customer = Customer::where('business_name', $request->business_name)->first();

        // we fetch the geo information of the given address
        list($lat, $long, $formatted_address, $street, $area) = $this->getLocation($request->address);
        if (!$customer) {
            $customer = new Customer();
            $customer->customer_type_id = $request->customer_type_id;
            $customer->tier_id = $request->tier_id;
            $customer->sub_region_id = $request->sub_region_id;
            $customer->region_id = $request->region_id;
            $customer->business_name = $request->business_name;
            $customer->email = $request->email;
            $customer->phone1 = $request->phone1;
            $customer->phone2 = $request->phone2;
            $customer->address = $formatted_address;
            $customer->street = $street;
            $customer->area = $area;
            $customer->longitude = $long;
            $customer->latitude = $lat;
            $customer->registered_by = $user->id;
            $customer->relating_officer = $user->id;
            $customer->save();

            return $this->show($customer);
        }
        return response()->json(['message' => 'Customer Exists']);
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
        $customer = $customer::with(['customerType', 'tier', 'subRegion', 'region', 'registrar', 'assignedOfficer', 'verifier', 'payments.confirmer', 'payments.transaction.staff' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'transactions'])->first();
        return response()->json(compact('customer'), 200);
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
