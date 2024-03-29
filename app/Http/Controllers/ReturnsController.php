<?php

namespace App\Http\Controllers;

use App\Models\ReturnedProduct;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReturnsController extends Controller
{
    //
    public function repsDailyReturns()
    {
        $user = $this->getUser();
        $today = date('Y-m-d', strtotime('now'));
        $returns = ReturnedProduct::with('customer', 'rep', 'item')
            ->where('stocked_by', $user->id)
            ->where('created_at', 'LIKE', '%' . $today . '%')
            ->orderBy('id', 'DESC')
            ->get();
        return response()->json(compact('returns'), 200);
    }
    public function fetchReturnedProducts(Request $request)
    {
        $user = $this->getUser();
        $paginate_option = $request->paginate_option;
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
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

            $returnsQuery = ReturnedProduct::with('customer', 'rep', 'item')
                ->where('stocked_by', $user->id)
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->orderBy('id', 'DESC');
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $returnsQuery = ReturnedProduct::with('customer', 'rep', 'item')
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('stocked_by', $sales_reps_ids)
                ->orderBy('id', 'DESC');
        } else {
            $returnsQuery = ReturnedProduct::with('customer', 'rep', 'item')
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->orderBy('id', 'DESC');
            // $sales = Transaction::with(['customer.assignedOfficer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        }
        if ($paginate_option === 'all') {
            $returns = $returnsQuery->get();
        } else {
            $returns = $returnsQuery->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('returns', 'currency', 'date_from', 'date_to'), 200);
    }
    public function store(Request $request)
    {
        // return $request;
        $actor = $this->getUser();
        $unsaved_returns = json_decode(json_encode($request->unsaved_returns));
        foreach ($unsaved_returns as $unsaved_return) {

            if (isset($unsaved_return->rep_id) && $unsaved_return->rep_id != '') {

                $rep_id = $unsaved_return->rep_id;
                $user = User::find($rep_id);
            } else {
                $user = $actor;
            }
            if (isset($unsaved_return->returns) && $unsaved_return->returns !== '') {

                $details = json_decode(json_encode($unsaved_return->returns));
                foreach ($details as $detail) {

                    $return = new ReturnedProduct();
                    $return->customer_id = $unsaved_return->customer_id;
                    $return->item_id = $detail->product_id;
                    $return->expiry_date = date('Y-m-d', strtotime($detail->expiry_date));
                    $return->stocked_by = $user->id;
                    $return->quantity = $detail->quantity_returned;
                    $return->rate = $detail->rate;
                    $return->batch_no = $detail->batch_no;
                    $return->amount = $detail->amount;
                    $return->reason = $detail->reason;
                    $return->date = date('Y-m-d', strtotime('now'));
                    $return->save();
                }
            }
            $unsaved_return->purpose = 'product returns';
            $visit_obj = new Visit();
            $visit_obj->saveAsVisits($user, $unsaved_return);
        }
        return response()->json(['unsaved_list' => [], 'message' => 'success'], 200);
    }
    public function confirm(Request $request, ReturnedProduct $returned_product)
    {
        if ($returned_product->confirmed_by === NULL) {
            $user = $this->getUser();
            $returned_product->confirmed_by = $user->id;
            $returned_product->save();
            $title = "Returned product Confirmed";
            $description = $user->name . " successfully confirmed returned product with ID $returned_product->id";

            $this->logUserActivity($title, $description, $user);
            return 'success';
        }
    }
}
