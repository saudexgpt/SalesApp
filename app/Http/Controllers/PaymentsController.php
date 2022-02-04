<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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
        $delivery_status = $request->delivery_status;
        if ($user->hasRole('sales_rep')) {

            $payments = $user->customerPayments()->with(['customer.assignedOfficer', 'confirmer'])->where('payment_date', '<=',  $date_to)->where('payment_date', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $payments = Payment::with(['customer.assignedOfficer', 'confirmer'])->where('payment_date', '<=',  $date_to)->where('payment_date', '>=',  $date_from)->where($condition)->whereIn('received_by', $sales_reps_ids)->orderBy('id', 'DESC')->paginate(10);
        } else {
            $payments = Payment::with(['customer.assignedOfficer', 'confirmer'])->where('payment_date', '<=',  $date_to)->where('payment_date', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('payments', 'currency', 'date_from', 'date_to'), 200);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        $payment = Payment::with(['customer', 'confirmer'])->find($payment->id);
        return response()->json(compact('payment'), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function confirm(Request $request, Payment $payment)
    {
        $user = $this->getUser();
        $payment->confirmed_by = $user->id;
        $payment->save();
        $title = "Payment Confirmed";
        $description = $user->name . " successfully confirmed NGN$payment->amount paid by" . $payment->customer->business_name;
        $this->logUserActivity($title, $description, $user);
        return $this->show($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
