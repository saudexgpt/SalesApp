<?php

namespace App\Http\Controllers;

use App\Models\ReturnedProduct;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReturnsController extends Controller
{
    //
    public function fetchReturnedProducts(Request $request)
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

            // $sales = $user->transactions()->with(['customer.assignedOfficer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);

            $returns = ReturnedProduct::with('customer', 'rep', 'item')
                ->where('stocked_by', $user->id)
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->orderBy('id', 'DESC')
                ->paginate(10);
        } else {
            $returns = ReturnedProduct::with('customer', 'rep', 'item')
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->orderBy('id', 'DESC')
                ->paginate(10);
            // $sales = Transaction::with(['customer.assignedOfficer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('returns', 'currency', 'date_from', 'date_to'), 200);
    }
}
