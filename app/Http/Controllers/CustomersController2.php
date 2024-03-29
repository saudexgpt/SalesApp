<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerCall;
use App\Models\CustomerContact;
use App\Models\CustomerType;
use App\Models\CustomerVerification;
use App\Models\LocalGovernmentArea;
use App\Models\SampleCustomer;
use App\Models\Schedule;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CustomersController extends Controller
{
  const ITEM_PER_PAGE = 200;
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  private function uploadPhoto($avatar)
  {
    $upload_folder = 'uploads/customers';
    if ($avatar === '' || $avatar === null) {
      $path = $upload_folder . "/default.png";
    } else {

      $id = time();
      $path = "$upload_folder/" . $id . '.' . $avatar->format;

      file_put_contents($path, base64_decode($avatar->base64String));
    }
    $actualpath = "https://sales.3coretechnology.com/$path";
    // $actualpath = "http://localhost:8000/$path";
    return $actualpath;
  }

  public function fetchStateLGACustomers()
  {
    $states = State::with(['lgas.customers' => function ($q) {
      $q->where('relating_officer', '!=', 1)
        ->where('relating_officer', '!=', NULL);
    }])->orderBy('name')->get();
    return response()->json(compact('states'), 200);
  }
  public function index(Request $request)
  {

    $user = $this->getUser();
    $searchParams = $request->all();
    $userQuery = Customer::query();
    $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
    $keyword = Arr::get($searchParams, 'keyword', '');
    $verify_type = Arr::get($searchParams, 'verify_type', 'verified');
    if (!empty($keyword)) {
      $userQuery->where(function ($q) use ($keyword) {
        $q->where('business_name', 'LIKE', '%' . $keyword . '%');
        $q->orWhere('lga_text', 'LIKE', '%' . $keyword . '%');
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
    $with = [
      'customerContacts',
      'state.lgas',
      'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

      'visits' => function ($q) {
        $q->orderBy('id', 'DESC')->paginate(10);
      },

    ];
    if ($verify_type === 'verified') {
      $userQuery = $userQuery->Verified();
    } else if ($verify_type === 'unverified') {
      $userQuery = $userQuery->Unverified();
    }
    if (isset($request->customer_type_id)) {

      $customer_type_id = $request->customer_type_id;
      if ($customer_type_id != 'all') {
        $condition = array_merge($condition, ['customer_type_id' => $customer_type_id]);
      }
    }
    if ($user->hasRole('sales_rep')) {
      $customers = $userQuery->with($with)->where($condition)->where('relating_officer', $user->id)->orderBy('id', 'DESC')->paginate($limit);
      return response()->json(compact('customers'), 200);
    } else if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
      // $sales_reps_ids is in array form
      list($sales_reps, $sales_reps_ids) = $this->teamMembers();
      $customers = $userQuery->with($with)->where($condition)->whereIn('relating_officer', $sales_reps_ids)->orderBy('id', 'DESC')->paginate($limit);
    } else if ($user->isManager()) {
      // manager only
      list($field, $domain_ids_array) = $this->managersDomain();
      if ($field !== '') {

        $customers = $userQuery->with($with)->where($condition)->whereIn($field, $domain_ids_array)->orderBy('id', 'DESC')->paginate($limit);
      } else {
        return response()->json(['message' => 'Manager Has No Coverage Area'], 403);
      }
    } else {
      // admin and super admin only
      $customers = $userQuery->with($with)->where($condition)->orderBy('id', 'DESC')->paginate($limit);
    }
    $customer_types = CustomerType::get();
    $states = State::with('lgas')->get();
    return response()->json(compact('customers', 'customer_types', 'states'), 200);
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
    $with = [
      'customerContacts',
      'customerType', /*'tier', 'subRegion', 'region',*/ 'registrar', 'assignedOfficer',

      'visits' => function ($q) {
        $q->orderBy('id', 'DESC')->paginate(10);
      },


    ];
    if (isset($request->customer_type_id)) {

      $customer_type_id = $request->customer_type_id;
      if ($customer_type_id != 'all') {
        $condition = array_merge($condition, ['customer_type_id' => $customer_type_id]);
      }
    }
    if ($user->hasRole('sales_rep')) {
      $customers = $userQuery->Unverified()->with($with)->where($condition)->where('relating_officer', $user->id)->orderBy('id', 'DESC')->paginate($limit);
    } else if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
      // $sales_reps_ids is in array form
      list($sales_reps, $sales_reps_ids) = $this->teamMembers();
      $customers = $userQuery->Unverified()->with($with)->where($condition)->whereIn('relating_officer', $sales_reps_ids)->orderBy('id', 'DESC')->paginate($limit);
    } else if ($user->isManager()) {
      // manager only
      list($field, $domain_ids_array) = $this->managersDomain();
      if ($field !== '') {

        $customers = $userQuery->Unverified()->with($with)->where($condition)->whereIn($field, $domain_ids_array)->orderBy('id', 'DESC')->paginate($limit);
      } else {
        return response()->json(['message' => 'Manager Has No Coverage Area'], 403);
      }
    } else {
      // admin and super admin only
      $customers = $userQuery->Unverified()->with($with)->where($condition)->orderBy('id', 'DESC')->paginate($limit);
    }

    return response()->json(compact('customers'), 200);
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
    } else if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
      // $sales_reps_ids is in array form
      list($sales_reps, $sales_reps_ids) = $this->teamMembers();
      $customers = Customer::with('customerContacts')->whereIn('relating_officer', $sales_reps_ids)->get();
    } else if ($user->isManager()) {
      // manager only
      list($field, $domain_ids_array) = $this->managersDomain();
      if ($field !== '') {

        $customers = Customer::with('customerContacts')->whereIn($field, $domain_ids_array)->orderBy('get');
      } else {
        return response()->json(['message' => 'Manager Has No Coverage Area'], 403);
      }
    } else {
      // admin and super admin only
      $customers = Customer::with('customerContacts')->get();
    }

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
      'state',
      'lga',
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
    $paginate = $request->paginate;
    if ($paginate < 1) {
      $paginate = 100;
    }
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
    ])->where($condition)->orderBy('business_name')->paginate($paginate);
    return response()->json(compact('customers'), 200);
  }
  private function saveCustomersDetails($unsaved_customer, $status = 'Prospective')
  {

    $user = $this->getUser();
    $lat = $long = $reg_lat = $reg_lng = $street = $area = NULL;
    $reg_mode = 'offline';
    if (isset($unsaved_customer->registrar_lat, $unsaved_customer->registrar_lng)) {
      $reg_lat = $unsaved_customer->registrar_lat;
      $reg_lng = $unsaved_customer->registrar_lng;
    }
    if (isset($unsaved_customer->customer_latitude, $unsaved_customer->customer_longitude)) {
      $lat = $unsaved_customer->customer_latitude;
      $long = $unsaved_customer->customer_longitude;
      $reg_mode = 'online';
    } else {
      $lat = $reg_lat;
      $long = $reg_lng;
    }

    $customer = Customer::where(['business_name' => $unsaved_customer->business_name, 'latitude' => $lat, 'longitude' => $long])->first();


    if (!$customer) {

      $street = $unsaved_customer->street;
      $area = $unsaved_customer->area;

      $formatted_address = $unsaved_customer->address;


      // we fetch the geo information of the given address
      if ($formatted_address !== '') {

        if ($lat == '' && $long == '' &&  $area == '') {
          list($lat, $long, $formatted_address, $street, $area) = $this->getLocationFromAddress($formatted_address);
        }
      }
      $contacts = json_decode(json_encode($unsaved_customer->customer_contacts));
      $customer = new Customer();
      $customer->status = $status;
      $customer->registration_mode = $reg_mode;
      $customer->customer_type_id = $unsaved_customer->customer_type_id;
      $customer->tier_id = $unsaved_customer->tier_id;
      $customer->lga_id = $unsaved_customer->lga_id;
      $customer->state_id = $unsaved_customer->state_id;
      $customer->lga_text = (isset($unsaved_customer->lga_text)) ? $unsaved_customer->lga_text : NULL;
      $customer->business_name = $unsaved_customer->business_name;
      // $customer->email = $unsaved_customer->email;
      // $customer->phone1 = $unsaved_customer->phone1;
      $customer->photo = $this->uploadPhoto($unsaved_customer->avatar);
      // $customer->base64_encoded_image = $unsaved_customer->avatar;
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

        // $customer_list[] = $this->show($customer);
        if ($status == 'Unverified') {
          $title = "Unverified Customer Added";
          $description = "New prospective customer, $customer->business_name, was added by " . $user->name;
          $this->logUserActivity($title, $description, $user);
        }

        return $customer;
      }
      // Generate notification before returning ///////////////////////
      // Write notification code here////////////////////////////


    }
    return $customer;
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
    $unsaved_list = [];
    foreach ($unsaved_customers as $unsaved_customer) {
      try {
        $customer = $this->saveCustomersDetails($unsaved_customer);
        $customer_list[] = $this->show($customer);
      } catch (\Throwable $th) {
        $unsaved_list[] =  $unsaved_customer;
      }
    }
    return response()->json(['customers' => $customer_list, 'unsaved_list' => $unsaved_list, 'message' => 'success'], 200);
  }

  public function update(Request $request, Customer $customer)
  {
    $customer->state_id = $request->state_id;
    $customer->lga_id = $request->lga_id;
    $customer->business_name = $request->business_name;
    $customer->customer_type_id = $request->customer_type_id;
    $customer->latitude = $request->latitude;
    $customer->longitude = $request->longitude;
    $customer->street = $request->street;
    $customer->address = $request->address;
    $customer->area = $request->area;
    $customer->save();
    return response()->json([], 204);
  }
  public function storeBulkCustomers(Request $request)
  {
    set_time_limit(0);
    // $actor = $this->getUser();
    $bulk_data = json_decode(json_encode($request->bulk_data));
    $unsaved_customers = [];
    $error = [];
    // try {
    foreach ($bulk_data as $data) {
      try {

        $business_name =  trim($data->BUSINESS_NAME);
        // $email =  trim($data->EMAIL);
        $address =  trim($data->ADDRESS);
        $area =  (isset($data->AREA)) ? trim($data->AREA) : NULL;
        $lga_text =  strtolower(trim($data->LGA));
        $cordinate =  trim($data->CORDINATE);
        $business_type = strtolower(trim($data->BUSINESS_TYPE));
        $contact_name =  (isset($data->CONTACT_PERSON)) ? trim($data->CONTACT_PERSON) : NULL;
        $contact_no = (isset($data->CONTACT_NUMBER)) ? trim($data->CONTACT_NUMBER) : NULL;

        $request->business_name = $business_name;
        $request->address = $address;
        $request->area = $area;
        $request->lga_text = $lga_text;
        // let's fetch the state_id and lga_id
        $lga = LocalGovernmentArea::where('name', 'LIKE', '%' . ucwords($lga_text) . '%')->first();
        if ($lga) {
          $request->lga_id = $lga->id;
          $request->state_id = $lga->state_id;
        }

        // let's extract the latitude and longitude from cordinate
        if ($cordinate !== '') {
          $cordinate_array = explode(',', $cordinate); // since cordinate is in form of (lat, lng)
          $request->customer_latitude = str_replace(' ', '', $cordinate_array[0]);
          $request->customer_longitude = str_replace(' ', '', $cordinate_array[1]);
        }
        // fetch the customer type
        $customer_type = CustomerType::where('name', 'LIKE', '%' . ucwords($business_type) . '%')->first();
        if (!$customer_type) {
          $customer_type = new CustomerType();
          $customer_type->name = ucwords($business_type);
          $customer_type->save();
        }
        $request->customer_type_id = $customer_type->id;

        $request->customer_contacts = [['name' => $contact_name, 'phone1' => $contact_no, 'phone2' => NULL, 'role' => NULL]];

        $this->saveCustomersDetails($request, 'Confirmed');
      } catch (\Throwable $th) {

        $unsaved_customers[] = $data;
        $error[] = $th;
      }
    }
    return response()->json(compact('unsaved_customers', 'error'), 200);
  }
  private function saveCustomerContact($customer_id, $contacts)
  {
    foreach ($contacts as $contact) {
      if ($contact->name !== NULL && $contact->phone1 !== NULL) {

        $new_contact = new CustomerContact();
        $new_contact->customer_id = $customer_id;
        $new_contact->name = $contact->name;
        $new_contact->phone1 = $contact->phone1;
        $new_contact->phone2 = $contact->phone2;
        $new_contact->role = $contact->role;
        $new_contact->save();
      }
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
      if (count($customer->customerContacts) > 0) {
        foreach ($customer->customerContacts as $customerContact) {
          $customerContact->delete();
        }
      }
      $this->saveCustomerContact($customer_id, $contacts);

      $title = "Customer Contacts Added";
      $description = "Contacts added for $customer->business_name";
      $this->logUserActivity($title, $description, $user);
    }
    $contacts = CustomerContact::where('customer_id', $customer_id)->get();
    return $contacts;
  }
  public function removeCustomerContact(CustomerContact $contact)
  {
    $user = $this->getUser();
    $customer = $contact->customer;
    $title = "Customer Contacts Removed";
    $description = $user->name . " removed $contact->name from contact list of $customer->business_name";
    $contact->delete();

    $this->logUserActivity($title, $description, $user);
    return response()->json([], 204);
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
      'customerContacts', 'customerType', 'tier', 'subRegion', 'region', 'registrar', 'state',
      'lga', 'assignedOfficer', 'verifier',
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

    $customer->verified_by = $user->id;
    $customer->date_verified = $today;
    $customer->save();

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
    $customer->status = 'Verified';
    $customer->save();

    $title = "Customer Confirmation Successful";
    $description = $user->name . " successfully confirmed $customer->business_name on $today";
    $this->logUserActivity($title, $description, $user);

    // return $this->prospectiveCustomers($request);
  }

  public function assignFieldStaff(Request $request, User $relating_officer)
  {
    $today  = date('Y-m-d', strtotime('now'));
    $user = $this->getUser();
    $customer_ids = json_decode(json_encode($request->customer_ids));
    $customers_assigned = '';
    foreach ($customer_ids as $customer_id) {
      $customer = Customer::find($customer_id);
      $customer->relating_officer = $relating_officer->id;
      $customer->save();
      $customers_assigned .= $customer->business_name . ', ';
    }

    $title = "Customer's Relating Officer Assigned";
    $description = $user->name . " successfully assigned $relating_officer->name to $customers_assigned on $today";
    $this->logUserActivity($title, $description, $relating_officer);

    // return $this->prospectiveCustomers($request);
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
    $user = $this->getUser();
    $title = "Customer Removed";
    $description = $user->name . " removed $customer->business_name from list of customers";
    $this->logUserActivity($title, $description, $user);
    $customer->delete();
    return response()->json([], 204);
  }
  public function reportDuplicate(Customer $customer)
  {
    $customer->is_duplicate_entry = 1;
    $customer->save();
    return $this->customerDetails($customer);
    // return response()->json(compact('customer_detail'), 200);
  }
}
