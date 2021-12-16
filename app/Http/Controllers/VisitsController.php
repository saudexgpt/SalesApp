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
    public function index(Request $request)
    {
        $user = $this->getUser();
        $visits = $user->visits()->with('customer', 'visitedBy', 'details')->orderBy('id', 'DESC')->get();
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
        } else {
            $hospital_reports = HospitalReport::with('customer', 'dailyReport.reporter')
                ->join('daily_reports', 'daily_reports.id', 'hospital_reports.daily_report_id')
                ->where('daily_reports.report_by',  $user->id)
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $this->getUser();
        $customer_id = $request->customer_id;
        $date = date('Y-m-d', strtotime('now'));
        $visit = Visit::where(['customer_id' => $customer_id, 'visitor' => $user->id, 'visit_date' => $date])->first();
        if (!$visit) {
            $visit = new Visit();
            $visit->customer_id = $customer_id;
            $visit->visitor = $user->id;
            $visit->visit_date = $date;
            $visit->save();
        }
        $this->saveVisitDetails($request, $visit);
        return $this->show($visit);
    }
    private function saveVisitDetails($request, $visit)
    {
        $user = $this->getUser();
        $visit_detail = new VisitDetail();
        $visit_detail->visit_id = $visit->id;
        $visit_detail->customer_contact_id = $request->contact_id;
        $visit_detail->visit_type = $request->visit_type;
        $visit_detail->purpose = $request->purpose;
        $visit_detail->description = $request->description;
        $visit_detail->save();

        $title = "New customer visit made";
        $description = $user->name . " made a new visit to $visit->customer->business_name";
        $this->logUserActivity($title, $description, $user);
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
        $visit =  $visit->with('customer', 'visitedBy', 'details')->find($visit->id);
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
