<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Http\Request;

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
        // undelivered transactions are considered orders
        $visits = $user->visits()->with('customer', 'visitedBy', 'details')->orderBy('id', 'DESC')->get();
        return response()->json(compact('visits'), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
