<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Payment;
use App\Models\Transaction;
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
        $date_from = Carbon::now()->startOfQuarter();
        $date_to = Carbon::now()->endOfQuarter();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
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

            $payments = Payment::groupBy(['payment_date', 'customer_id'])
                ->with(['customer', 'confirmer'])
                ->where('received_by', $user->id)
                ->where('payment_date', '<=',  $date_to)
                ->where('payment_date', '>=',  $date_from)->where($condition)
                ->orderBy('id', 'DESC')
                ->select('*', \DB::raw('SUM(amount) as total_amount'))
                ->paginate(10);
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $payments = Payment::groupBy('payment_date', 'customer_id')
                ->with(['customer.assignedOfficer', 'confirmer'])
                ->where('payment_date', '<=',  $date_to)
                ->where('payment_date', '>=',  $date_from)
                ->where($condition)
                ->whereIn('received_by', $sales_reps_ids)
                ->orderBy('id', 'DESC')
                ->select('*', \DB::raw('SUM(amount) as total_amount'))
                ->paginate(10);
        } else {
            $payments = Payment::groupBy('payment_date', 'customer_id')
                ->with(['customer.assignedOfficer', 'confirmer'])
                ->where('payment_date', '<=',  $date_to)
                ->where('payment_date', '>=',  $date_from)
                ->where($condition)
                ->orderBy('id', 'DESC')
                ->select('*', \DB::raw('SUM(amount) as total_amount'))
                ->paginate(10);
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
        $unsaved_collections = json_decode(json_encode($request->unsaved_collections));
        $unsaved_list = [];
        foreach ($unsaved_collections as $collection) {
            try {
                $original_amount = $collection->amount_collected;
                $amount = $collection->amount_collected;
                $user = $this->getUser();
                $customer_id = $collection->customer_id;

                $date = date('Y-m-d', strtotime('now'));

                $payment = new Payment();
                $payment->customer_id = $customer_id;
                $payment->amount = $amount;
                $payment->payment_date = $date;
                $payment->payment_type = $collection->payment_mode;
                $payment->slip_no = $collection->slip_no;
                $payment->received_by = $user->id;
                $payment->save();

                // $customer_debts = CustomerDebt::where('customer_id', $customer_id)->whereRaw('amount - paid > 0')->orderBy('id')->get();

                // foreach ($customer_debts as $customer_debt) {
                //     $debt = $customer_debt->amount - $customer_debt->paid;
                //     if ($debt <= $amount) {

                //         $customer_debt->paid += $debt;
                //         $customer_debt->payment_status = 'paid';
                //         $customer_debt->save();

                //         $payment = new Payment();
                //         $payment->debt_id = $customer_debt->id;
                //         $payment->customer_id = $customer_debt->customer_id;
                //         $payment->amount = $debt;
                //         $payment->payment_date = $date;
                //         $payment->payment_type = $collection->payment_mode;
                //         $payment->received_by = $user->id;
                //         $payment->save();

                //         $amount -= $debt;
                //     } else {
                //         $customer_debt->paid += $amount;
                //         $customer_debt->save();

                //         $payment = new Payment();
                //         $payment->debt_id = $customer_debt->id;
                //         $payment->customer_id = $customer_debt->customer_id;
                //         $payment->amount = $amount;
                //         $payment->payment_date = $date;
                //         $payment->payment_type = $collection->payment_mode;
                //         $payment->received_by = $user->id;
                //         $payment->save();
                //         $amount = 0;
                //         break;
                //     }
                // }


                $title = "Payment Received";
                $description = $user->name . " successfully received ₦$original_amount from $collection->business_name";
                $this->logUserActivity($title, $description, $user);
            } catch (\Throwable $th) {
                $unsaved_list[] = $collection;
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
        $customer_id = $payment->customer_id;
        $amount = $payment->amount;
        // $payment->save();
        if ($payment->save()) {
            //we need to make this approved payment affect debts
            $customer_debts = CustomerDebt::where('customer_id', $customer_id)->whereRaw('amount - paid > 0')->orderBy('id')->get();

            foreach ($customer_debts as $customer_debt) {
                $debt = $customer_debt->amount - $customer_debt->paid;
                if ($debt <= $amount) {

                    $customer_debt->paid += $debt;
                    $customer_debt->payment_status = 'paid';
                    $customer_debt->save();

                    $debt_payment = new CustomerDebtPayment();
                    $debt_payment->customer_id = $customer_id;
                    $debt_payment->debt_id = $customer_debt->id;
                    $debt_payment->payment_id = $payment->id;
                    $debt_payment->amount_paid = $debt;
                    $debt_payment->save();

                    $amount -= $debt;
                } else {
                    $customer_debt->paid += $amount;
                    $customer_debt->save();

                    $debt_payment = new CustomerDebtPayment();
                    $debt_payment->customer_id = $customer_id;
                    $debt_payment->debt_id = $customer_debt->id;
                    $debt_payment->payment_id = $payment->id;
                    $debt_payment->amount_paid = $amount;
                    $debt_payment->save();

                    $amount = 0;
                    break;
                }
            }
        }
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
    public function destroy(Payment $payment)
    {
        //
    }
}
