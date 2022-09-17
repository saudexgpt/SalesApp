<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Visit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentsController extends Controller
{
    public function repsDailyCollections()
    {
        $user = $this->getUser();
        $today = date('Y-m-d', strtotime('now'));
        $payments = Payment::groupBy(['payment_date', 'customer_id'])
            ->with(['customer', 'confirmer'])
            ->where('received_by', $user->id)
            ->where('payment_date', 'LIKE', '%' . $today . '%')
            ->orderBy('id', 'DESC')
            ->select('*', \DB::raw('SUM(amount) as total_amount'))
            ->get();
        return response()->json(compact('payments'), 200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        $paginate_option = $request->paginate_option;
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
        }
        $limit = 25;
        if (isset($request->limit) && $request->limit != '') {
            $limit = $request->limit;
        }
        $rep_field_name = 'received_by';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        $delivery_status = $request->delivery_status;
        $total_collections = 0;
        if ($user->hasRole('sales_rep')) {

            $paymentsQuery = Payment::groupBy(['payment_date', 'customer_id'])
                ->with(['customer', 'confirmer'])
                ->where('received_by', $user->id)
                ->where('payment_date', '>=',  $date_from)
                ->where('payment_date', '<=',  $date_to)
                ->where($condition)
                ->orderBy('payment_date', 'DESC')
                ->select('*', \DB::raw('SUM(amount) as total_amount'));
        }
        // else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
        //     // $sales_reps_ids is in array form
        //     list($sales_reps, $sales_reps_ids) = $this->teamMembers();
        //     $paymentsQuery = Payment::groupBy('payment_date', 'customer_id')
        //         ->with(['customer.assignedOfficer', 'confirmer'])
        //         ->where('payment_date', '<=',  $date_to)
        //         ->where('payment_date', '>=',  $date_from)
        //         ->whereIn('received_by', $sales_reps_ids)
        //         ->where($condition)
        //         ->orderBy('id', 'DESC')
        //         ->select('*', \DB::raw('SUM(amount) as total_amount'));
        // }
        else {
            list($sales_reps, $sales_reps_ids) = $this->teamMembers($request->team_id);
            $paymentsQuery = Payment::groupBy('payment_date', 'customer_id')
                ->with(['customer.assignedOfficer', 'confirmer'])
                ->where('payment_date', '<=',  $date_to)
                ->where('payment_date', '>=',  $date_from)
                ->whereIn('received_by', $sales_reps_ids)
                ->where($condition)
                ->orderBy('id', 'DESC')
                ->select('*', \DB::raw('SUM(amount) as total_amount'));

            $total_collections = Payment::where('payment_date', '<=',  $date_to)
                ->where('payment_date', '>=',  $date_from)
                ->where($condition)
                ->whereIn('received_by', $sales_reps_ids)
                ->select(\DB::raw('SUM(amount) as total_amount'))->first();
        }
        if ($paginate_option === 'all') {
            $payments = $paymentsQuery->get();
        } else {
            $payments = $paymentsQuery->paginate($limit);
        }
        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('payments', 'currency', 'total_collections', 'date_from', 'date_to'), 200);
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
        $unsaved_collections = json_decode(json_encode($request->unsaved_collections));
        $unsaved_list = [];
        foreach ($unsaved_collections as $collection) {

            $original_amount = $collection->amount_collected;
            $amount = $collection->amount_collected;
            $user = $this->getUser();
            $customer_id = $collection->customer_id;

            $date = date('Y-m-d', strtotime('now'));
            $entry_exist = Payment::where('unique_collection_id', $collection->unique_collection_id)->first();
            if (!$entry_exist) {
                try {
                    $payment = new Payment();
                    $payment->customer_id = $customer_id;
                    $payment->unique_collection_id = $collection->unique_collection_id;
                    $payment->amount = $amount;
                    $payment->payment_date = $date;
                    $payment->payment_type = $collection->payment_mode;
                    $payment->slip_no = $collection->slip_no;
                    $payment->received_by = $user->id;
                    if ($payment->save()) {


                        $customer_debt_obj = new CustomerDebt();
                        $customer_debt_obj->settleDebt($customer_id);

                        $visit_obj = new Visit();
                        $visit_obj->saveAsVisits($user, $collection);

                        $title = "Payment Received";
                        $description = $user->name . " successfully received ₦$original_amount from $collection->business_name";
                        $this->logUserActivity($title, $description, $user);
                    }
                } catch (\Throwable $th) {
                    $unsaved_list[] = $collection;
                }
            }
        }
        return response()->json(['unsaved_list' => $unsaved_list, 'message' => 'success'], 200);
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
        // $customer_id = $payment->customer_id;
        $amount = $payment->amount;
        $payment->save();
        $title = "Payment Confirmed";
        $description = $user->name . " successfully confirmed NGN$amount paid by" . $payment->customer->business_name;
        $this->logUserActivity($title, $description, $user);
        return $this->show($payment);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
}
