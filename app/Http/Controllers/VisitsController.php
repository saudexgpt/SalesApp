<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerProductSample;
use App\Models\CustomerStockBalance;
use App\Models\HospitalReport;
use App\Models\Prescription;
use App\Models\Schedule;
use App\Models\User;
use App\Models\UserGeolocation;
use App\Models\VanInventory;
use App\Models\Visit;
use App\Models\VisitDetail;
use App\Models\VisitDetailedProduct;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VisitsController extends Controller
{
    // public function test()
    // {
    //     $x = 3;
    //     $y = $this->foo($x);

    //     return $x . $y;
    // }
    // private function foo(&$args)
    // {
    //     $z = $args;
    //     $args += 1;
    //     return $z;
    // }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $user = $this->getUser();
        $today = date('Y-m-d', strtotime('now'));
        if (isset($request->date) && $request->date !== '') {
            $date = date('Y-m-d', strtotime($request->date));
            $visits = $user->visits()->with('customer', 'visitedBy', 'contact', 'details.contact', 'detailings', 'customerStockBalances', 'customerSamples')->orderBy('id', 'DESC')->where('created_at', 'LIKE', '%' . $date . '%')->get();
        } else {
            $yesterday = date('Y-m-d', strtotime('yesterday'));
            $visits = $user->visits()->with('customer', 'visitedBy', 'contact', 'details.contact', 'detailings', 'customerStockBalances', 'customerSamples')->orderBy('id', 'DESC')->where('created_at', 'LIKE', '%' . $yesterday . '%')->get();
        }
        $today_visits = $user->visits()->with('customer', 'visitedBy', 'contact', 'details.contact', 'detailings', 'customerStockBalances', 'customerSamples')
            ->where('created_at', 'LIKE', '%' . $today . '%')->orderBy('id', 'DESC')->get();
        return response()->json(compact('visits', 'today_visits'), 200);
    }

    public function fetchHospitalVisits(Request $request)
    {
        $user = $this->getUser();
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to, $request->panel)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }
        $condition = [];
        if (isset($request->customer_id) && $request->customer_id != 'all') {
            $condition = ['customer_id' => $request->customer_id];
        }

        if ($user->hasRole('sales_rep')) {

            $hospital_reports = HospitalReport::with('customer', 'dailyReport.reporter')
                ->join('daily_reports', 'daily_reports.id', 'hospital_reports.daily_report_id')
                ->where('daily_reports.report_by',  $user->id)
                ->where('hospital_reports.created_at', '<=',  $date_to)
                ->where('hospital_reports.created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $hospital_reports = HospitalReport::with('customer', 'dailyReport.reporter')
                ->join('daily_reports', 'daily_reports.id', 'hospital_reports.daily_report_id')
                ->whereIn('daily_reports.report_by', $sales_reps_ids)
                ->where('hospital_reports.created_at', '<=',  $date_to)
                ->where('hospital_reports.created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        } else {
            $hospital_reports = HospitalReport::with('customer', 'dailyReport.reporter')
                ->join('daily_reports', 'daily_reports.id', 'hospital_reports.daily_report_id')
                ->where('hospital_reports.created_at', '<=',  $date_to)
                ->where('hospital_reports.created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('hospital_reports', 'currency', 'date_from', 'date_to'), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function fetchGeneralVisits(Request $request)
    {
        $user = $this->getUser();
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to, $request->panel)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }
        $condition = [];
        if (isset($request->customer_id) && $request->customer_id != 'all') {
            $condition['customer_id'] = $request->customer_id;
        }
        if (isset($request->rep_id) && $request->rep_id != 'all') {
            $condition['visitor'] = $request->rep_id;
        }

        if ($user->hasRole('sales_rep')) {

            $visits = Visit::with('visitedBy', 'visitPartner', 'customer', 'contact', 'details', 'detailings.item', 'customerStockBalances.item', 'customerSamples.item')
                ->where('visitor',  $user->id)
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $visits = Visit::with('visitedBy', 'visitPartner', 'customer', 'contact', 'details', 'detailings.item', 'customerStockBalances.item', 'customerSamples.item')
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('visits.visitor', $sales_reps_ids)
                ->paginate(10);
        } else {
            $visits = Visit::with('visitedBy', 'visitPartner', 'customer', 'contact', 'details', 'detailings.item', 'customerStockBalances.item', 'customerSamples.item')
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('visits', 'currency', 'date_from', 'date_to'), 200);
    }

    // public function fetchGeneralVisits(Request $request)
    // {
    //     $user = $this->getUser();
    //     $date_from = Carbon::now()->startOfMonth();
    //     $date_to = Carbon::now()->endOfMonth();
    //     $panel = 'quarter';
    //     $currency = $this->currency();
    //     if (isset($request->from, $request->to, $request->panel)) {
    //         $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
    //         $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
    //         $panel = $request->panel;
    //     }
    //     $condition = [];
    //     if (isset($request->customer_id) && $request->customer_id != 'all') {
    //         $condition = ['customer_id' => $request->customer_id];
    //     }

    //     if ($user->hasRole('sales_rep')) {

    //         $visit_details = VisitDetail::with('visit.visitedBy', 'visit.customer', 'contact')
    //             ->join('visits', 'visits.id', 'visit_details.visit_id')
    //             ->where('visits.visitor',  $user->id)
    //             ->where('visit_details.created_at', '<=',  $date_to)
    //             ->where('visit_details.created_at', '>=',  $date_from)
    //             ->where($condition)
    //             ->paginate(10);
    //     } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
    //         // $sales_reps_ids is in array form
    //         list($sales_reps, $sales_reps_ids) = $this->teamMembers();
    //         $visit_details = VisitDetail::with('visit.visitedBy', 'visit.customer', 'contact')
    //             ->join('visits', 'visits.id', 'visit_details.visit_id')
    //             ->where('visit_details.created_at', '<=',  $date_to)
    //             ->where('visit_details.created_at', '>=',  $date_from)
    //             ->where($condition)
    //             ->whereIn('visits.visitor', $sales_reps_ids)
    //             ->paginate(10);
    //     } else {
    //         $visit_details = VisitDetail::with('visit.visitedBy', 'visit.customer', 'contact')
    //             ->join('visits', 'visits.id', 'visit_details.visit_id')
    //             ->where('visit_details.created_at', '<=',  $date_to)
    //             ->where('visit_details.created_at', '>=',  $date_from)
    //             ->where($condition)
    //             ->paginate(10);
    //     }

    //     $date_from = getDateFormatWords($date_from);
    //     $date_to = getDateFormatWords($date_to);
    //     return response()->json(compact('visit_details', 'currency', 'date_from', 'date_to'), 200);
    // }
    // public function fetchFootPrints(Request $request)
    // {
    //     $user = $this->getUser();
    //     $date = date('Y-m-d', strtotime('now'));
    //     $currency = $this->currency();
    //     if (isset($request->date) && $request->date !== '') {
    //         $date = date('Y-m-d', strtotime($request->date));
    //     }
    //     $condition = [];
    //     if (isset($request->user_id) && $request->user_id != 'all') {
    //         $condition['visitor'] = $request->user_id;
    //     }
    //     if ($user->hasRole('sales_rep')) {

    //         $visits = Visit::with('details.contact', 'customer', 'visitedBy')
    //             ->where('visitor',  $user->id)
    //             ->where('created_at', 'LIKE', '%' . $date . '%')
    //             ->get();
    //     } else {
    //         $visits = Visit::with('details.contact', 'customer', 'visitedBy')
    //             ->where($condition)
    //             ->where('created_at', 'LIKE', '%' . $date . '%')
    //             ->get();
    //     }

    //     $date = getDateFormatWords($date);
    //     return response()->json(compact('visits', 'currency', 'date'), 200);
    // }
    public function fetchFootPrints(Request $request)
    {
        $user = $this->getUser();
        $date = date('Y-m-d', strtotime('now'));
        if (isset($request->date) && $request->date !== '') {
            $date = date('Y-m-d', strtotime($request->date));
        }
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
        } else {
            $userQuery = User::query();

            $userQuery->whereHas('roles', function ($q) {
                $q->where('name', 'sales_rep');
            });

            $sales_reps = $userQuery->get();
        }
        $reps_not_located = [];
        $reps_located = [];
        foreach ($sales_reps as $sales_rep) {
            $location = UserGeolocation::where('user_id',  $sales_rep->id)
                ->where('created_at', 'LIKE', '%' . $date . '%')
                ->orderBy('id', 'DESC')
                ->first();
            if ($location) {
                $sales_rep->location = $location;
                $reps_located[] = $sales_rep;
            } else {
                $reps_not_located[] = $sales_rep;
            }
        }

        $date = getDateFormatWords($date);
        return response()->json(compact('reps_located', 'reps_not_located', 'date'), 200);
    }
    public function repTodayVisits(Request $request)
    {
        $rep_id = $request->rep_id;
        $date = date('Y-m-d', strtotime($request->date));
        $visits = Visit::with('visitedBy', 'visitPartner', 'customer', 'contact', 'details', 'detailings.item', 'customerStockBalances.item', 'customerSamples.item')
            ->where('visitor',  $rep_id)
            ->where('created_at', 'LIKE', '%' . $date . '%')
            ->get();
        return response()->json(compact('visits'), 200);
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
        $unsaved_visits = json_decode(json_encode($request->unsaved_visits));
        $visit_list = [];
        $visit_obj = new Visit();
        $unsaved_list = $visit_obj->saveAsVisits($user, $unsaved_visits);

        $visits = $user->visits()->with('customer', 'visitedBy', 'details.contact')->orderBy('id', 'DESC')->take(100)->get();
        return response()->json(compact('visits', 'unsaved_list'), 200);
        // foreach ($unsaved_visits as $unsaved_visit) {

        //     $customer_id = $unsaved_visit->customer_id;
        //     $customer = Customer::find($customer_id);
        //     $lat = $unsaved_visit->rep_latitude;
        //     $long = $unsaved_visit->rep_longitude;
        //     $visit_date = $unsaved_visit->visit_date;
        //     $customer_contact_id = ($unsaved_visit->contact_id) ? $unsaved_visit->contact_id : null;

        //     $purposes = json_decode(json_encode($unsaved_visit->purposes));
        //     $purpose = implode(',', $purposes);
        //     $visit_type = 'off site';
        //     $distance = NULL;
        //     if ($lat != '') {

        //         $distance = haversineGreatCircleDistanceBetweenTwoPoints(
        //             $customer->latitude,
        //             $customer->longitude,
        //             $lat,
        //             $long,
        //         );
        //         // we are giving 100 meter allowance
        //         if ($distance < 100) {
        //             $visit_type = 'on site';
        //         }
        //     }
        //     try {
        //         $date = ($visit_date) ? date('Y-m-d', strtotime($visit_date)) : date('Y-m-d', strtotime('now'));
        //         $visit = Visit::where(['customer_id' => $customer_id, 'visitor' => $user->id, 'visit_date' => $date])->first();

        //         if (!$visit) {

        //             $visit = new Visit();
        //             $visit->customer_id = $customer_id;
        //             $visit->visitor = $user->id;
        //             $visit->visit_date = $date;
        //             $visit->rep_latitude = $lat;
        //             $visit->rep_longitude = $long;
        //             $visit->address = $formatted_address;
        //             $visit->accuracy = $unsaved_visit->accuracy;
        //             $visit->proximity = $distance;
        //             $visit->visiting_partner_id = (isset($unsaved_visit->visiting_partner_id)) ? $unsaved_visit->visiting_partner_id : NULL;
        //             $visit->customer_contact_id = $customer_contact_id;
        //             $visit->visit_type = $visit_type;
        //             $visit->purpose = $purpose;
        //             $visit->description = $unsaved_visit->description;
        //             $visit->visit_duration = $unsaved_visit->visit_duration;
        //             $visit->next_appointment_date = date('Y-m-d H:i:s', strtotime($unsaved_visit->hospital_follow_up_schedule));
        //             $visit->save();
        //         }
        //         // $this->saveVisitDetails($unsaved_visit, $visit);

        //         if (isset($unsaved_visit->hospital_follow_up_schedule) && $unsaved_visit->hospital_follow_up_schedule != null) {

        //             $this->saveSchedule($customer_id, $unsaved_visit->hospital_follow_up_schedule);
        //         }

        //         if (isset($unsaved_visit->prescriptions) && !empty($unsaved_visit->prescriptions)) {
        //             $this->savePrescriptions($unsaved_visit, $visit);
        //         }
        //         if (isset($unsaved_visit->detailed_products) && !empty($unsaved_visit->detailed_products)) {
        //             $this->saveDetailedProducts($unsaved_visit, $visit);
        //         }
        //         if (isset($unsaved_visit->stock_balances) && !empty($unsaved_visit->stock_balances)) {
        //             $this->saveStockBalances($unsaved_visit, $visit);
        //         }
        //         if (isset($unsaved_visit->samples) && !empty($unsaved_visit->samples)) {
        //             $this->saveSamples($unsaved_visit, $visit);
        //         }
        //     } catch (\Throwable $th) {
        //         $unsaved_list[] = $unsaved_visit;
        //     }
        // }
        // $visits = $user->visits()->with('customer', 'visitedBy', 'details.contact')->orderBy('id', 'DESC')->take(100)->get();
        // return response()->json(compact('visits', 'unsaved_list'), 200);
    }
    // private function saveVisitDetails($unsaved_visit, $visit)
    // {
    //     $user = $this->getUser();
    //     $purposes = json_decode(json_encode($unsaved_visit->purposes));
    //     $purpose = implode(',', $purposes);
    //     $customer_contact_id = $unsaved_visit->contact_id;

    //     $visit_type = 'off site';
    //     if ($visit->rep_latitude != '') {

    //         $distance = haversineGreatCircleDistanceBetweenTwoPoints(
    //             $visit->customer->latitude,
    //             $visit->customer->longitude,
    //             $visit->rep_latitude,
    //             $visit->rep_longitude,
    //         );
    //         // we are giving 100 meter allowance
    //         if ($distance < 100) {
    //             $visit_type = 'on site';
    //         }
    //     }
    //     $purpose = $purpose;
    //     $description = $unsaved_visit->description;

    //     $visit_detail = VisitDetail::where(['visit_id' => $visit->id, 'customer_contact_id' => $customer_contact_id, 'visit_type' => $visit_type, 'purpose' => $purpose, 'description' => $description])->first();
    //     if (!$visit_detail) {
    //         $visit_detail = new VisitDetail();
    //         $visit_detail->visit_id = $visit->id;
    //         $visit_detail->customer_contact_id = $customer_contact_id;
    //         $visit_detail->visit_type = $visit_type;
    //         $visit_detail->purpose = $purpose;
    //         $visit_detail->description = $description;
    //         $visit_detail->save();

    //         $title = "New customer visit made";
    //         $description = $user->name . " made a new visit to " . $visit->customer->business_name;
    //         $this->logUserActivity($title, $description, $user);
    //     }
    // }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Visit  $visit
     * @return \Illuminate\Http\Response
     */
    public function show(Visit $visit)
    {
        //
        $visit =  $visit->with('customer', 'visitedBy', 'details.contact')->find($visit->id);
        return response()->json(compact('visit'), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Visit  $visit
     * @return \Illuminate\Http\Response
     */
    public function edit(Visit $visit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Visit  $visit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Visit $visit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Visit  $visit
     * @return \Illuminate\Http\Response
     */
    public function destroy(Visit $visit)
    {
        //
    }
}
