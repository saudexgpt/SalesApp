<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function saleRepDashboard()
    {
        $today = date('Y-m-d', strtotime('now'));
        $user = $this->getUser();
        $currency = $this->currency();

        // let's fetch this user's customers
        $customers = $user->customers;

        $sales = $user->transactions()->count();
        // $payment = $user->transactions()->where('payment_status', 'paid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();
        $all_debt = $user->transactions()->where('payment_status', 'unpaid')->select(\DB::raw('SUM(amount_due) as amount_due'))->first();

        $all_overdue = $user->transactions()->where('payment_status', 'unpaid')->where('due_date', '<=', $today)->select(\DB::raw('SUM(amount_due) as amount_due'))->first();

        $overdue = 0;
        $debt = 0;
        if ($all_overdue) {
            $overdue = ($all_overdue->amount_due) ? $all_overdue->amount_due : 0;
        }
        if ($all_debt) {
            $debt = ($all_debt->amount_due) ? $all_debt->amount_due : 0;
        }


        return response()->json(compact('customers', 'sales', 'debt', 'overdue', 'currency'), 200);
    }
}
