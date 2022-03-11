<?php

namespace App\Http\Controllers;

use App\Models\HospitalReport;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VisitsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = $this->getUser();
        $visits = $user->visits()->with('customer', 'visitedBy', 'details.contact')->orderBy('id', 'DESC')->get();
        return response()->json(compact('visits'), 200);
    }

    public function fetchHospitalVisits(Request $request)
    {
        $user = $this->getUser();
        $date_from = Carbon::now()->startOfQuarter();
        $date_to = Carbon::now()->endOfQuarter();
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
        $date_from = Carbon::now()->startOfQuarter();
        $date_to = Carbon::now()->endOfQuarter();
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

            $visit_details = VisitDetail::with('visit.visitedBy', 'visit.customer', 'contact')
                ->join('visits', 'visits.id', 'visit_details.visit_id')
                ->where('visits.visitor',  $user->id)
                ->where('visit_details.created_at', '<=',  $date_to)
                ->where('visit_details.created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $visit_details = VisitDetail::with('visit.visitedBy', 'visit.customer', 'contact')
                ->join('visits', 'visits.id', 'visit_details.visit_id')
                ->where('visit_details.created_at', '<=',  $date_to)
                ->where('visit_details.created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('visits.visitor', $sales_reps_ids)
                ->paginate(10);
        } else {
            $visit_details = VisitDetail::with('visit.visitedBy', 'visit.customer', 'contact')
                ->join('visits', 'visits.id', 'visit_details.visit_id')
                ->where('visit_details.created_at', '<=',  $date_to)
                ->where('visit_details.created_at', '>=',  $date_from)
                ->where($condition)
                ->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('visit_details', 'currency', 'date_from', 'date_to'), 200);
    }

    public function fetchFootPrints(Request $request)
    {
        $user = $this->getUser();
        $date = date('Y-m-d', strtotime('now'));
        $currency = $this->currency();
        if (isset($request->date) && $request->date !== '') {
            $date = date('Y-m-d', strtotime($request->date));
        }

        if ($user->hasRole('sales_rep')) {

            $visits = Visit::with('details.contact', 'customer', 'visitedBy')
                ->where('visitor',  $user->id)
                ->where('created_at', 'LIKE', '%' . $date . '%')
                ->get();
        } else {
            $visits = Visit::with('details.contact', 'customer', 'visitedBy')
                ->where('visitor', $request->user_id)
                ->where('created_at', 'LIKE', '%' . $date . '%')
                ->get();
        }

        $date = getDateFormatWords($date);
        return response()->json(compact('visits', 'currency', 'date'), 200);
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
        $unsaved_list = [];
        foreach ($unsaved_visits as $unsaved_visit) {
            $lat = $unsaved_visit->rep_latitude;
            $long = $unsaved_visit->rep_longitude;
            try {
                $customer_id = $unsaved_visit->customer_id;
                $date = date('Y-m-d', strtotime('now'));
                $visit = Visit::where(['customer_id' => $customer_id, 'visitor' => $user->id, 'visit_date' => $date])->first();
                if (!$visit) {
                    $str_response = $this->getLocationFromLatLong($lat, $long);
                    $json = json_decode($str_response);
                    $formatted_address = $json->{'results'}[0]->{'formatted_address'};
                    $visit = new Visit();
                    $visit->customer_id = $customer_id;
                    $visit->visitor = $user->id;
                    $visit->visit_date = $date;
                    $visit->rep_latitude = $lat;
                    $visit->rep_longitude = $long;
                    $visit->address = $formatted_address;
                    $visit->accuracy = $unsaved_visit->accuracy;
                    $visit->save();
                }
                $this->saveVisitDetails($unsaved_visit, $visit);
            } catch (\Throwable $th) {
                $unsaved_list[] = $unsaved_visit;
            }
        }
        $visits = $user->visits()->with('customer', 'visitedBy', 'details.contact')->orderBy('id', 'DESC')->take(10)->get();
        return response()->json(compact('visits', 'unsaved_list'), 200);
    }
    private function saveVisitDetails($unsaved_visit, $visit)
    {
        $user = $this->getUser();
        $purposes = json_decode(json_encode($unsaved_visit->purposes));
        $purpose = implode(',', $purposes);
        $customer_contact_id = $unsaved_visit->contact_id;

        $visit_type = 'off site';
        if ($visit->rep_latitude != '') {

            $distance = haversineGreatCircleDistanceBetweenTwoPoints(
                $visit->customer->latitude,
                $visit->customer->longitude,
                $visit->rep_latitude,
                $visit->rep_longitude,
            );
            // we are giving 100 meter allowance
            if ($distance < 100) {
                $visit_type = 'on site';
            }
        }
        $purpose = $purpose;
        $description = $unsaved_visit->description;

        $visit_detail = VisitDetail::where(['visit_id' => $visit->id, 'customer_contact_id' => $customer_contact_id, 'visit_type' => $visit_type, 'purpose' => $purpose, 'description' => $description])->first();
        if (!$visit_detail) {
            $visit_detail = new VisitDetail();
            $visit_detail->visit_id = $visit->id;
            $visit_detail->customer_contact_id = $customer_contact_id;
            $visit_detail->visit_type = $visit_type;
            $visit_detail->purpose = $purpose;
            $visit_detail->description = $description;
            $visit_detail->save();

            $title = "New customer visit made";
            $description = $user->name . " made a new visit to " . $visit->customer->business_name;
            $this->logUserActivity($title, $description, $user);
        }
    }

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
