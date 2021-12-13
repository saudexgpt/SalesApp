<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
        $day = date('l', strtotime('now'));
        $user = $this->getUser();
        $currency = $this->currency();

        // let's fetch this user's customers
        $customers = $user->customers;

        /// $sales = Transaction::where('payment_status', 'paid')->count();
        $all_sales = Transaction::select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        // $payment = Transaction::where('payment_status', 'paid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        $all_debt = Transaction::where('payment_status', 'unpaid')->select(\DB::raw('SUM(amount_due - amount_paid) as amount_due'))->first();

        $all_overdue = Transaction::where('payment_status', 'unpaid')->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount_due - amount_paid) as amount_due'))->first();

        $overdue = 0;
        $debt = 0;
        $sales = 0;
        if ($all_overdue) {
            $overdue = ($all_overdue->amount_due) ? $all_overdue->amount_due : 0;
        }
        if ($all_debt) {
            $debt = ($all_debt->amount_due) ? $all_debt->amount_due : 0;
        }
        if ($all_sales) {
            $sales = ($all_sales->amount_due) ? $all_sales->amount_due : 0;
        }

        $today_orders = Transaction::with('customer', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        $today_visits = $user->visits()->with('customer', 'visitedBy', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        $today_schedule = $user->mySchedules()->with('customer')->where('schedule_date', $today)->orWhere(function ($q) use ($day) {
            $q->where('repeat_schedule', 'yes');
            $q->where('day', $day);
        })->get();

        return response()->json(compact('user', 'customers', 'sales', 'debt', 'overdue', 'currency', 'today_orders', 'today_visits', 'today_schedule'), 200);
    }

    public function dashboard()
    {

        $date_from = Carbon::now()->startOfYear();
        $date_to = Carbon::now()->endOfYear();

        $today = date('Y-m-d', strtotime('now'));
        $day = date('l', strtotime('now'));
        $user = $this->getUser();
        $currency = $this->currency();

        // let's fetch this user's customers
        $customers = Customer::get();

        /// $sales = Transaction::where('payment_status', 'paid')->count();
        $all_sales = Transaction::select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        // $payment = Transaction::where('payment_status', 'paid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        $all_debt = Transaction::where('payment_status', 'unpaid')->select(\DB::raw('SUM(amount_due - amount_paid) as amount_due'))->first();

        $all_overdue = Transaction::where('payment_status', 'unpaid')->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount_due - amount_paid) as amount_due'))->first();

        $overdue = 0;
        $debt = 0;
        $sales = 0;
        if ($all_overdue) {
            $overdue = ($all_overdue->amount_due) ? $all_overdue->amount_due : 0;
        }
        if ($all_debt) {
            $debt = ($all_debt->amount_due) ? $all_debt->amount_due : 0;
        }
        if ($all_sales) {
            $sales = ($all_sales->amount_due) ? $all_sales->amount_due : 0;
        }

        $today_orders = Transaction::with('customer', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        $today_visits = Visit::with('customer', 'visitedBy', 'details')->where('created_at', '>=', $today)->orderBy('id', 'DESC')->get();

        $today_schedule = Schedule::with('customer')->where('schedule_date', $today)->orWhere(function ($q) use ($day) {
            $q->where('repeat_schedule', 'yes');
            $q->where('day', $day);
        })->get();

        return response()->json(compact('user', 'customers', 'sales', 'debt', 'overdue', 'currency', 'today_orders', 'today_visits', 'today_schedule'), 200);
    }

    public function transactionStat(Request $request)
    {
        $year = (isset($request->from) && $request->from != '') ? date('Y', strtotime($request->from)) : date('Y', strtotime('now'));
        $sales_array = [];
        $debts_array = [];
        for ($month = 1; $month <= 12; $month++) {
            if ($month < 10) {

                $date = $year . '-0' . $month;
            } else {

                $date = $year . '-' . $month;
            }
            list($sales, $debts) = $this->getSalesAndDebts($date);
            $sales_array[] = $sales;
            $debts_array[] = $debts;
        }
        $series = [
            [
                'name' => 'Sales (NGN)',
                'data' => $sales_array
            ],
            [
                'name' => 'Debts (NGN)',
                'data' => $debts_array
            ]
        ];
        return response()->json(compact('series'), 200);
    }
    private function getSalesAndDebts($date)
    {
        $all_sales = Transaction::select(\DB::raw('SUM(amount_due) as total_amount_due'))->where('entry_date', 'LIKE',  '%' . $date . '%')->first();
        $all_debts = Transaction::where('entry_date', 'LIKE',  '%' . $date . '%')->select(\DB::raw('SUM(amount_due - amount_paid) as total_amount_due'))->first();

        $sales = 0;
        $debts = 0;
        if ($all_sales) {
            $sales = $all_sales->total_amount_due;
        }
        if ($all_debts) {
            $debts = $all_debts->total_amount_due * -1;
        }
        return array($sales, $debts);
    }
}
