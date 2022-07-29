<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\Visit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    //
    public function saleRepDashboard()
    {
        $today = date('Y-m-d', strtotime('now'));
        $this_year = date('Y', strtotime('now'));
        $day = date('l', strtotime('now'));
        $user = $this->getUser();

        $user->last_login = date('Y-m-d H:i:s', strtotime('now'));
        $user->save();
        $currency = $this->currency();

        // let's fetch this user's customers
        $customers = $user->customers;

        /// $sales = Transaction::where('payment_status', 'paid')->count();
        $all_sales = Transaction::where('field_staff', $user->id)->where('created_at', 'LIKE', '%' . $this_year . '%')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        // $payment = Transaction::where('payment_status', 'paid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        $all_debt = CustomerDebt::where('field_staff', $user->id)/*->where('payment_status', 'unpaid')*/->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $all_overdue = CustomerDebt::where('field_staff', $user->id)/*->where('payment_status', 'unpaid')*/->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $payments = Payment::where('received_by', $user->id)->where('payment_date', 'LIKE', '%' . $this_year . '%')->select(\DB::raw('SUM(amount) as total_amount'))->first();

        // $today_sales = Transaction::where('field_staff', $user->id)
        //     ->where('created_at', 'LIKE', '%' . $today . '%')
        //     ->select(\DB::raw('SUM(amount_due) as amount_due'))
        //     ->first();

        // $today_collections = Payment::where('received_by', $user->id)
        //     ->where('created_at', 'LIKE', '%' . $today . '%')
        //     ->select(\DB::raw('SUM(amount) as amount_paid'))
        //     ->first();

        // $today_debt = CustomerDebt::where('field_staff', $user->id)->where('payment_status', 'unpaid')->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $overdue = 0;
        $debt = 0;
        $sales = 0;
        $collections = 0;
        if ($all_overdue) {
            $overdue = ($all_overdue->amount_due) ? $all_overdue->amount_due : 0;
        }
        if ($all_debt) {
            $debt = ($all_debt->amount_due) ? $all_debt->amount_due : 0;
        }
        if ($all_sales) {
            $sales = ($all_sales->amount_due) ? $all_sales->amount_due : 0;
        }
        if ($payments) {
            $collections = ($payments->total_amount) ? $payments->total_amount : 0;
        }
        // $today_orders = Transaction::where('field_staff', $user->id)->with('customer', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        $today_visits = $user->visits()/*->with('customer', 'visitedBy', 'details')*/->where('created_at', 'LIKE', '%' . $today . '%')->orderBy('id', 'DESC')->get();

        $user->last_login = date('Y-m-d H:i:s', strtotime('now'));
        $user->save();

        return response()->json(compact('user', 'customers', 'sales', 'debt', 'overdue', 'collections', 'today_visits', 'currency'), 200);
    }

    public function managerDashboard()
    {
        $today = date('Y-m-d', strtotime('now'));
        $day = date('l', strtotime('now'));
        $date = date('Y-m', strtotime('now'));
        $month = date('F', strtotime('now'));
        $user = $this->getUser();

        $user->last_login = date('Y-m-d H:i:s', strtotime('now'));
        $user->save();
        $currency = $this->currency();
        list($sales_reps, $sales_reps_ids) = $this->teamMembers();

        // let's fetch this user's customers
        $customers = Customer::whereIn('relating_officer', $sales_reps_ids)->get();

        /// $sales = Transaction::where('payment_status', 'paid')->count();
        $all_sales = Transaction::whereIn('field_staff', $sales_reps_ids)->where('created_at', 'LIKE', '%' . $date . '%')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        // $payment = Transaction::where('payment_status', 'paid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        $all_debt = CustomerDebt::whereIn('field_staff', $sales_reps_ids)->where('payment_status', 'unpaid')->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $all_overdue = CustomerDebt::whereIn('field_staff', $sales_reps_ids)->where('payment_status', 'unpaid')->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $payments = Payment::whereIn('received_by', $sales_reps_ids)->where('payment_date', 'LIKE', '%' . $date . '%')->select(\DB::raw('SUM(amount) as total_amount'))->first();

        $overdue = 0;
        $debt = 0;
        $sales = 0;
        $collections = 0;
        if ($all_overdue) {
            $overdue = ($all_overdue->amount_due) ? $all_overdue->amount_due : 0;
        }
        if ($all_debt) {
            $debt = ($all_debt->amount_due) ? $all_debt->amount_due : 0;
        }
        if ($all_sales) {
            $sales = ($all_sales->amount_due) ? $all_sales->amount_due : 0;
        }
        if ($payments) {
            $collections = ($payments->total_amount) ? $payments->total_amount : 0;
        }
        return response()->json(compact('user', 'customers', 'sales', 'debt', 'collections', 'overdue', 'currency', 'sales_reps', 'month'), 200);
    }

    public function dashboard()
    {
        $date = date('Y-m', strtotime('now'));
        $month = date('F', strtotime('now'));

        $date_from = Carbon::now()->startOfYear();
        $date_to = Carbon::now()->endOfYear();

        $today = date('Y-m-d', strtotime('now'));
        $day = date('l', strtotime('now'));
        $user = $this->getUser();
        $currency = $this->currency();


        $user->last_login = date('Y-m-d H:i:s', strtotime('now'));
        $user->save();

        // let's fetch this user's customers
        $customers = Customer::get();

        /// $sales = Transaction::where('payment_status', 'paid')->count();
        $all_sales = Transaction::where('created_at', 'LIKE', '%' . $date . '%')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        // $payment = Transaction::where('payment_status', 'paid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        $all_debt = CustomerDebt::where('payment_status', 'unpaid')->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $all_overdue = CustomerDebt::where('payment_status', 'unpaid')->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount - paid) as amount_due'))->first();

        $payments = Payment::where('payment_date', 'LIKE', '%' . $date . '%')->select(\DB::raw('SUM(amount) as total_amount'))->first();

        $overdue = 0;
        $debt = 0;
        $sales = 0;
        $collections = 0;
        if ($all_overdue) {
            $overdue = ($all_overdue->amount_due) ? $all_overdue->amount_due : 0;
        }
        if ($all_debt) {
            $debt = ($all_debt->amount_due) ? $all_debt->amount_due : 0;
        }
        if ($all_sales) {
            $sales = ($all_sales->amount_due) ? $all_sales->amount_due : 0;
        }
        if ($payments) {
            $collections = ($payments->total_amount) ? $payments->total_amount : 0;
        }
        // $today_orders = Transaction::with('customer', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        // $today_visits = Visit::with('customer', 'visitedBy', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        // $today_schedule = Schedule::with('customer')->where('schedule_date', $today)->orWhere(function ($q) use ($day) {
        //     $q->where('repeat_schedule', 'yes');
        //     $q->where('day', $day);
        // })->get();

        return response()->json(compact('user', 'customers', 'sales', 'debt', 'collections', 'overdue', 'currency', 'month'), 200);
    }


    public function transactionStat(Request $request)
    {
        $year = (isset($request->from) && $request->from != '') ? date('Y', strtotime($request->from)) : date('Y', strtotime('now'));
        $sales_array = [];
        $collections_array = [];
        for ($month = 1; $month <= 12; $month++) {
            if ($month < 10) {

                $date = $year . '-0' . $month;
            } else {

                $date = $year . '-' . $month;
            }
            list($sales, $collections) = $this->getSalesAndDebts($request, $date);
            $sales_array[] = $sales;
            $collections_array[] = $collections;
        }
        $series = [
            [
                'name' => 'Sales',
                'data' => $sales_array
            ],
            [
                'name' => 'Collections',
                'data' => $collections_array
            ]
        ];
        return response()->json(compact('series'), 200);
    }
    private function getSalesAndDebts($request, $date)
    {
        $condition1 = $this->setQueryConditions($request, 'field_staff');
        $condition2 = $this->setQueryConditions($request, 'received_by');

        $all_sales = Transaction::where($condition1)->where('created_at', 'LIKE',  '%' . $date . '%')->select(\DB::raw('SUM(amount_due) as total_amount_due'))
            ->first();

        $all_collections = Payment::where('created_at', 'LIKE',  '%' . $date . '%')->where($condition2)->select(\DB::raw('SUM(amount) as total_amount'))->first();

        $sales = 0;
        $collections = 0;
        if ($all_sales) {
            $sales = $all_sales->total_amount_due;
        }
        if ($all_collections) {
            $collections = $all_collections->total_amount;
        }
        return array($sales, $collections);
    }

    public function repSalesAndDebtReport(Request $request)
    {
        $request->rep_id = $this->getUser()->id;
        $condition1 = $this->setQueryConditions($request, 'field_staff');
        $condition2 = $this->setQueryConditions($request, 'received_by');
        $year = $request->year;
        $this_year = ($year == 'now') ? date('Y', strtotime('now')) : $year;
        $customer_sales = Transaction::where($condition1)
            ->where('created_at', 'LIKE', '%' . $this_year . '%')
            ->get();
        $payments = Payment::where($condition2)
            ->where('payment_date', 'LIKE', '%' . $this_year . '%')
            ->get();
        $monthly_sales_amounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthly_collection_amounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($customer_sales as $customer_sale) {
            $month = (int) date('m', strtotime($customer_sale->created_at));
            $month_index = $month - 1;
            $monthly_sales_amounts[$month_index] += (float) $customer_sale->amount_due;
        }
        foreach ($payments as $payment) {
            $month = (int) date('m', strtotime($payment->payment_date));
            $month_index = $month - 1;
            $monthly_collection_amounts[$month_index] += (float) $payment->amount;
        }
        $series = [
            [
                'name' => 'Sales',
                'data' => $monthly_sales_amounts, //array format
                'color' => '#f39c12',
                // 'stack' => 'Initial Stock'
            ],
            [
                'name' => 'Collections',
                'data' => $monthly_collection_amounts, //array format
                //'color' => '#f39c12',
                //'stack' => 'In Stock'
            ],
            // [
            //     'name' => 'In Stock',
            //     'data' => $balance, //array format
            //     'color' => '#00a65a',
            //     'stack' => 'In Stock'
            // ],
            // [
            //     'name' => 'Supplied',
            //     'data' => $supplied, //array format
            //     'color' => '#DC143C',
            //     'stack' => 'Supplied'
            // ],
        ];
        $customer_debt = CustomerDebt::where($condition1)
            // ->whereRaw('amount - paid > 0')
            // ->where('payment_status', 'unpaid')
            ->select(\DB::raw('SUM(amount - paid) as debt'))
            ->first();
        $debts_and_payments = [
            $customer_debt->debt
        ];

        // foreach ($customer_debts as $customer_debt) {
        //     $debt = $customer_debt->amount - $customer_debt->paid;
        //     // if ($customer_debt->due_date <= $today) {
        //     //     // overdue debt
        //     //     $debts_and_payments[0] += (float) $debt;
        //     // } else {

        //     //     $debts_and_payments[1] += (float) $debt;
        //     // }
        //     $debts_and_payments[0] += (float) $debt;
        //     // $debts_and_payments[2] += (float) $customer_debt->paid;
        // }
        // $series = [
        //     [
        //         'name' => 'Debt',
        //         'data' => $debts_and_payments[0], //array format
        //         // 'color' => '#333333',
        //         // 'stack' => 'Initial Stock'
        //     ],
        //     [
        //         'name' => 'Paid',
        //         'data' => $debts_and_payments[1]
        //     ],
        // ];
        return response()->json(['sales' => $series, 'debt' => $debts_and_payments], 200);
        // return $debts_and_payments;
        // return $series;
    }
}
