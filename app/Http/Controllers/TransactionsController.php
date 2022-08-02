<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\Item;
use App\Models\Payment;
use App\Models\ReturnedProduct;
use App\Models\Schedule;
use App\Models\SubInventory;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VanInventory;
use App\Models\Visit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function repsDailySales()
    {
        $user = $this->getUser();
        $today = date('Y-m-d', strtotime('now'));
        //Today Sales
        $sales = $user->transactions()->with(['customer', 'details'])
            ->where('created_at', 'LIKE', '%' . $today . '%')
            ->orderBy('id', 'DESC')->get();
        return response()->json(compact('sales'), 200);
    }
    public function orders()
    {
        $user = $this->getUser();
        // undelivered transactions are considered orders
        $orders = $user->transactions()
            ->with(['customer', 'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            }, 'details'])
            ->orderBy('id', 'DESC')->get();
        return response()->json(compact('orders'), 200);
    }
    public function fetchDebts(Request $request)
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
        $rep_field_name = 'field_staff';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        $delivery_status = $request->delivery_status;
        $total_debts = 0;
        if ($user->hasRole('sales_rep')) {

            $debtsQuery = $user->debts()
                ->groupBy('customer_id')
                ->with([
                    'customer',
                ])
                ->whereRaw('amount - paid > 0')
                // ->where('created_at', '<=',  $date_to)
                // ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->select('*', \DB::raw('SUM(amount) as total_amount_due'), \DB::raw('SUM(paid) as total_amount_paid'));
            // ->get();

            // $debts = $user->transactions()->with(['customer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->whereRaw('amount_due - amount_paid > 0')->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(50);
        }
        // else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
        //     // $sales_reps_ids is in array form
        //     list($sales_reps, $sales_reps_ids) = $this->teamMembers();
        //     $debtsQuery = CustomerDebt::groupBy('customer_id')
        //         ->with([
        //             'staff',
        //             'customer.assignedOfficer',
        //             'payments' => function ($q) {
        //                 $q->orderBy('id', 'DESC');
        //             },
        //         ])
        //         ->whereRaw('amount - paid > 0')
        //         ->where('created_at', '<=',  $date_to)
        //         ->where('created_at', '>=',  $date_from)
        //         ->where($condition)
        //         ->whereIn('field_staff', $sales_reps_ids)
        //         ->select('*', \DB::raw('SUM(amount) as total_amount_due'), \DB::raw('SUM(paid) as total_amount_paid'));
        // }
        else {
            list($sales_reps, $sales_reps_ids) = $this->teamMembers($request->team_id);
            $debtsQuery = CustomerDebt::groupBy('customer_id')
                ->with([
                    'staff',
                    'customer.assignedOfficer',
                    'payments' => function ($q) {
                        $q->orderBy('id', 'DESC');
                    },
                ])
                ->whereRaw('amount - paid > 0')
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('field_staff', $sales_reps_ids)
                ->select('*', \DB::raw('SUM(amount) as total_amount_due'), \DB::raw('SUM(paid) as total_amount_paid'));
            // $debts = Transaction::with(['customer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->whereRaw('amount_due - amount_paid > 0')->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(50);

            $total_debts = CustomerDebt::where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('field_staff', $sales_reps_ids)
                ->select(\DB::raw('SUM(amount - paid) as total_amount'))->first();
        }
        if ($paginate_option === 'all' || $user->hasRole('sales_rep')) {
            $debts = $debtsQuery->get();
        } else {
            $debts = $debtsQuery->paginate(25);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('debts', 'currency', 'total_debts', 'date_from', 'date_to'), 200);
    }
    public function fetchSales(Request $request)
    {
        $user = $this->getUser();
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }
        $rep_field_name = 'field_staff';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        $delivery_status = $request->delivery_status;
        $total_sales = 0;
        if ($user->hasRole('sales_rep')) {

            $salesQuery = $user->transactions()
                ->with(['customer.assignedOfficer', 'details'])
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->orderBy('id', 'DESC');
        } else {
            list($sales_reps, $sales_reps_ids) = $this->teamMembers($request->team_id);
            // $sales_reps_ids is in array form
            // list($sales_reps, $sales_reps_ids) = $this->teamMembers();
            $salesQuery = Transaction::with(['customer', 'staff'])
                ->where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('field_staff', $sales_reps_ids)
                ->orderBy('id', 'DESC');

            $total_sales = Transaction::where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('field_staff', $sales_reps_ids)
                ->select(\DB::raw('SUM(amount_due) as total_amount'))->first();
        }
        // else {
        //     $salesQuery = Transaction::with(['customer.assignedOfficer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC');
        // }
        $paginate_option = $request->paginate_option;
        if ($paginate_option === 'all') {
            $sales = $salesQuery->get();
        } else {
            $sales = $salesQuery->paginate(25);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('sales', 'currency', 'date_from', 'date_to', 'total_sales'), 200);
    }
    public function fetchProductSales(Request $request)
    {
        $user = $this->getUser();
        $paginate_option = $request->paginate_option;
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
        }
        $rep_field_name = 'transaction_details.field_staff';
        $condition = $this->setQueryConditions($request, $rep_field_name);

        $delivery_status = $request->delivery_status;
        $total_sales = 0;
        if ($user->hasRole('sales_rep')) {

            $salesQuery = TransactionDetail::with('transaction.customer')
                ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                ->join('users', 'transactions.field_staff', 'users.id')
                ->where('transactions.field_staff', $user->id)
                ->where('transaction_details.created_at', '<=',  $date_to)
                ->where('transaction_details.created_at', '>=',  $date_from)
                ->where($condition)
                ->orderBy('transaction_details.id', 'DESC');
        }
        // else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
        //     // $sales_reps_ids is in array form
        //     list($sales_reps, $sales_reps_ids) = $this->teamMembers($request->team_id);
        //     $salesQuery = TransactionDetail::with('transaction.customer')->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
        //         ->join('users', 'transactions.field_staff', 'users.id')->where('transaction_details.created_at', '<=',  $date_to)->where('transaction_details.created_at', '>=',  $date_from)->where($condition)->whereIn('transactions.field_staff', $sales_reps_ids)->orderBy('transaction_details.id', 'DESC');
        // }
        else {
            list($sales_reps, $sales_reps_ids) = $this->teamMembers($request->team_id);
            $salesQuery = TransactionDetail::with('transaction.customer')
                ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                ->join('users', 'transactions.field_staff', 'users.id')
                ->where('transaction_details.created_at', '<=',  $date_to)
                ->where('transaction_details.created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('transactions.field_staff', $sales_reps_ids)
                ->orderBy('transaction_details.id', 'DESC');
            // $sales = Transaction::with(['customer.assignedOfficer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(50);

            $total_sales = TransactionDetail::where('created_at', '<=',  $date_to)
                ->where('created_at', '>=',  $date_from)
                ->where($condition)
                ->whereIn('field_staff', $sales_reps_ids)
                ->select(\DB::raw('SUM(amount) as total_amount'))->first();
        }
        if ($paginate_option === 'all') {
            $sales = $salesQuery->get();
        } else {
            $sales = $salesQuery->paginate(25);
        }
        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('sales', 'total_sales', 'currency', 'date_from', 'date_to'), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $prefix = 'INV-';
        $user = $this->getUser();
        $unsaved_orders = json_decode(json_encode($request->unsaved_orders));
        $order_list = [];
        $unsaved_list = [];
        foreach ($unsaved_orders as $unsaved_order) {

            $date = $unsaved_order->due_date;
            $invoice_items = $unsaved_order->invoice_items;
            $entry_exist = Transaction::where('unique_sales_id', $unsaved_order->unique_sales_id)->first();
            if (!$entry_exist) {
                # code...
                try {
                    $invoice = new Transaction();
                    $invoice->customer_id    = $unsaved_order->customer_id;
                    $invoice->unique_sales_id    = $unsaved_order->unique_sales_id;
                    $invoice->field_staff    = $user->id;
                    $invoice->payment_status = ($unsaved_order->payment_mode == 'now') ? 'paid' : 'unpaid';
                    $invoice->amount_due     = $unsaved_order->amount;
                    $invoice->main_amount     = $unsaved_order->main_amount;

                    $invoice->due_date       = ($unsaved_order->payment_mode == 'later') ? date('Y-m-d', strtotime($date)) : date('Y-m-d', strtotime('now'));
                    $invoice->entry_date = ($request->entry_date != '') ? date('Y-m-d H:i:s', strtotime($request->entry_date)) : date('Y-m-d H:i:s', strtotime('now'));
                    if ($invoice->save()) {

                        $invoice->invoice_no = $this->getInvoiceNo($prefix, $invoice->id);
                        $invoice->save();

                        $this->addCustomerDebt($invoice);
                        $this->createInvoiceItems($invoice, $invoice_items);
                        $customer_debt_obj = new CustomerDebt();
                        $customer_debt_obj->settleDebt($invoice->customer_id);

                        //uncomment this if the sales feature for the app is reactivated
                        // if ($unsaved_order->payment_mode == 'now') {
                        //     $this->makePayments($invoice);
                        // }

                        // if (isset($unsaved_order->amount_collected) && $unsaved_order->amount_collected > 0) {
                        //     $this->payAmount($invoice, $unsaved_order->amount_collected);
                        // }

                        $visit_obj = new Visit();
                        $visit_obj->saveAsVisits($user, $unsaved_order);

                        // $order_list[] = $this->show($invoice);

                        $customer = Customer::find($unsaved_order->customer_id);
                        $title = "New Sales made";
                        $description = $user->name . " successfully made sales to $customer->business_name";
                        $this->logUserActivity($title, $description, $user);
                    }
                    // $title = "New order received";
                    // $description = "New $invoice->payment_status order ($invoice->invoice_no) was generated by $user->name";
                    //log this action to invoice history
                    // $this->createInvoiceHistory($invoice, $title, $description);
                    //create items invoiceed for
                } catch (\Throwable $th) {
                    $unsaved_list[] = $unsaved_order;
                }
            }
        }
        //////update next invoice number/////
        // $this->incrementReceiptNo('invoice');

        //log this activity
        // $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
        // $this->logUserActivity($title, $description, $roles);
        return response()->json(['orders' => $order_list, 'unsaved_list' => $unsaved_list, 'message' => 'success'], 200);
    }
    private function addCustomerDebt($transaction)
    {
        $new_debt = new CustomerDebt();
        $new_debt->customer_id = $transaction->customer_id;
        $new_debt->transaction_id = $transaction->id;
        $new_debt->amount = $transaction->amount_due;
        $new_debt->field_staff = $transaction->field_staff;
        $new_debt->due_date = $transaction->due_date;
        $new_debt->save();
    }
    private function createInvoiceItems($invoice, $invoice_items)
    {
        foreach ($invoice_items as $item) {
            $delivery_mode = $item->delivery_mode;
            $invoice_item = new TransactionDetail();
            $invoice_item->transaction_id = $invoice->id;
            $invoice_item->field_staff = $invoice->field_staff;
            $invoice_item->item_id = $item->item_id;
            $invoice_item->product = Item::find($item->item_id)->name;
            $invoice_item->quantity = $item->quantity;
            $invoice_item->batch_no = $item->batch_no;
            $invoice_item->expiry_date = $item->expiry_date;

            // if ($delivery_mode == 'now') {
            //     // set quantity for supply
            //     $invoice_item->quantity_supplied = $item->quantity_supplied;
            // }
            $invoice_item->packaging = $item->type;
            $invoice_item->main_rate = $item->main_rate;
            $invoice_item->main_amount = $item->main_amount;
            $invoice_item->rate = $item->rate;
            $invoice_item->amount = $item->amount;
            $invoice_item->save();

            if ($delivery_mode == 'later') {
                // set schedule for delivery
                $this->scheduleDeliveryDate($invoice->customer_id, $invoice_item, $item->delivery_date);
            }
            if ($delivery_mode == 'now') {
                $this->performProductSupply($invoice_item, $item->quantity_supplied, $item->item_id);
            }
        }
    }

    private function scheduleDeliveryDate($customer_id, $item, $delivery_date)
    {
        $date = new \DateTime($delivery_date);
        // $date->modify('+ 1 hour'); // add one hour to get the normal time in our time zone
        $schedule_date = $date->format('Y-m-d'); //  date('Y-m-d', strtotime($delivery_date));
        $schedule_time = $date->format('H:i:s');
        $user = $this->getUser();
        // $schedule_date = date('Y-m-d', strtotime($delivery_date));
        // $schedule_time = date('H:i:s', strtotime($delivery_date));
        $customer_id = $customer_id;
        $rep = $user->id;
        $note = 'Delivery of ' . $item->quantity . ' ' . $item->packaging . ' of ' . $item->product;
        $repeat_schedule = 'no';
        $day = date('l', strtotime($schedule_date)); // returns 'Monday' or 'Tuesday' , etc
        $day_num = workingDaysStr($day);
        $schedule = new Schedule();
        $schedule->day = $day;
        $schedule->day_num = $day_num;
        $schedule->schedule_date = $schedule_date;
        $schedule->schedule_time = $schedule_time;
        $schedule->customer_id = $customer_id;
        $schedule->rep = $rep;
        $schedule->note = $note;
        $schedule->repeat_schedule = $repeat_schedule;
        $schedule->scheduled_by = $user->id;
        $schedule->save();
    }

    private function makePayments($transaction)
    {
        $user = $this->getUser();
        $date = ($transaction->entry_date != null) ? $transaction->entry_date : date('Y-m-d', strtotime('now'));;
        // $batches = $item->batches;
        $payment = new Payment();
        $payment->transaction_id = $transaction->id;
        $payment->customer_id = $transaction->customer_id;
        $customer = Customer::find($payment->customer_id);
        $payment->amount = $transaction->amount_due;
        $payment->payment_date = $date;
        $payment->received_by = $user->id;
        if ($payment->save()) {
            $transaction->amount_paid = $transaction->amount_due;
            $transaction->save();

            $title = "Payment Received";
            $description = $user->name . " successfully received ₦$transaction->amount_due from $customer->business_name";
            $this->logUserActivity($title, $description, $user);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        //
        $transaction = $transaction->with(['customer', 'details' => function ($q) {
            $q->orderBy('id', 'DESC');
        },])->find($transaction->id);
        return $transaction;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function supplyOrders(Request $request, TransactionDetail $transaction_detail)
    {
        $item_id = $transaction_detail->item_id;
        $quantity_for_supply = $request->quantity_for_supply;

        return $this->performProductSupply($transaction_detail, $quantity_for_supply, $item_id);
    }
    private function performProductSupply(TransactionDetail $transaction_detail, $quantity_for_supply, $item_id)
    {
        $user = $this->getUser();
        $stock_balance = $this->checkForStockBalance($item_id);
        if ($quantity_for_supply > $stock_balance) {
            return response()->json(['message' => 'Insufficient Stock'], 422);
        }
        $quantity = $transaction_detail->quantity;
        $packaging = $transaction_detail->packaging;
        $product = $transaction_detail->product;
        $customer = $transaction_detail->transaction->customer;

        $total_quantity_supplied = $quantity_for_supply + $transaction_detail->quantity_supplied;
        if ($total_quantity_supplied <= $quantity) {
            $transaction_detail->quantity_supplied = $total_quantity_supplied;
            if ($transaction_detail->quantity_supplied == $quantity) {
                $transaction_detail->supply_status = 'Completely Supplied';
            } else {
                $transaction_detail->supply_status = 'Partially Supplied';
            }

            $transaction_detail->save();
            $van_inventory_obj = new VanInventory();
            $van_inventory_obj->deductFromVanInventory($item_id, $quantity_for_supply, $user);

            $title = "Product Supplied";
            $description = $user->name . " supplied $quantity $packaging of $product to $customer->business_name";
            $this->logUserActivity($title, $description, $user);
        }
        $transaction = $transaction_detail->transaction()->with('details')->first();
        $is_partial = 0;
        foreach ($transaction->details as $detail) {
            if ($detail->supply_status != 'Completely Supplied') {
                $is_partial++;
            }
        }
        if ($is_partial === 0) {
            $transaction->delivery_status = 'delivered';
            $transaction->save();
        }
        return $this->show($transaction);
    }
    private function checkForStockBalance($item_id)
    {
        $user = $this->getUser();
        $balance = VanInventory::where(['staff_id' => $user->id, 'item_id' => $item_id])->sum('balance');
        return $balance;
    }


    public function customerStatement(Request $request)
    {
        $customer_id = $request->customer_id;
        $customer = Customer::find($customer_id);
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'month';
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }
        list($total_debt_till_date, $past_payments_till_date, $past_returns_till_date, $payments, $returns, $debts) = $this->getCustomerTransactions($customer_id, $date_from, $date_to);

        $past_debt = ($total_debt_till_date) ? $total_debt_till_date->total_amount_due : 0;
        $past_payments = ($past_payments_till_date) ? $past_payments_till_date->total_amount_paid : 0;
        $past_returns = ($past_returns_till_date) ? $past_returns_till_date->total_amount_returned : 0;

        $brought_forward = (int)$past_debt - ((int) $past_payments + (int) $past_returns);
        $statements = [];
        if ($payments->isNotEmpty()) {
            foreach ($payments as $payment) {
                //$running_balance += $inbound->quantity;
                $statements[]  = [
                    'type' => 'paid',
                    'amount_transacted' => $payment->total_amount_paid,
                    'description' => 'Collection',
                    'date' => $payment->created_at,
                    'debt' => '',
                    'paid' => $payment->total_amount_paid,
                    'balance' => 0, // initially set to zero
                    // 'packaging' => $inbound->item->package_type,
                    // 'physical_quantity' => '',
                    // 'sign' => '',
                ];
            }
        }
        if ($returns->isNotEmpty()) {
            foreach ($returns as $return) {
                //$running_balance += $inbound->quantity;
                $statements[]  = [
                    'type' => 'paid',
                    'amount_transacted' => $return->total_amount_returned,
                    'description' => 'Returns',
                    'date' => $return->created_at,
                    'debt' => '',
                    'paid' => $return->total_amount_returned,
                    'balance' => 0, // initially set to zero
                    // 'packaging' => $inbound->item->package_type,
                    // 'physical_quantity' => '',
                    // 'sign' => '',
                ];
            }
        }
        if ($debts->isNotEmpty()) {
            foreach ($debts as $debt) {
                //$running_balance -= $outbound->quantity_supplied;
                $statements[] = [
                    'type' => 'debt',
                    'amount_transacted' => $debt->amount,
                    'description' => ($debt->transaction_id !== NULL) ? 'Sales' : 'Debt',
                    'date' => $debt->created_at,
                    'debt' => $debt->amount,
                    'paid' => '',
                    'balance' => 0, // initially set to zero
                    // 'packaging' => $outbound->itemStock->item->package_type,
                    // 'physical_quantity' => '',
                    // 'sign' => '',
                ];
            }
        }
        usort($statements, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        $date_from_formatted = date('Y-m-d', strtotime($date_from));
        $date_to_formatted = date('Y-m-d', strtotime($date_to));
        return  response()->json(compact('statements', 'brought_forward', 'date_from_formatted', 'date_to_formatted', 'customer'), 200);
    }

    private function getCustomerTransactions($customer_id, $date_from, $date_to)
    {

        // $total_debt_till_date = Transaction::groupBy('customer_id')
        //     ->where('customer_id', $customer_id)
        //     ->where('entry_date', '<', $date_from)
        //     // ->where('confirmed_by', '!=', null)
        //     ->select('*', \DB::raw('SUM(amount_due) as total_amount_due'))
        //     ->first();
        $total_debt_till_date = CustomerDebt::groupBy('customer_id')
            ->where('customer_id', $customer_id)
            ->where('created_at', '<', $date_from)
            // ->where('confirmed_by', '!=', null)
            ->select('*', \DB::raw('SUM(amount) as total_amount_due'))
            ->first();
        $past_payments_till_date = Payment::groupBy('customer_id')
            ->where('customer_id', $customer_id)
            ->where('payment_date', '<', $date_from)
            // ->where('confirmed_by', '!=', null)
            ->select('*', \DB::raw('SUM(amount) as total_amount_paid'))
            ->first();
        $past_returns_till_date = ReturnedProduct::groupBy('customer_id')
            ->where('customer_id', $customer_id)
            ->where('date', '<', $date_from)
            // ->where('confirmed_by', '!=', null)
            ->select('*', \DB::raw('SUM(amount) as total_amount_returned'))
            ->first();

        $payments = Payment::groupBy('payment_date')
            ->where(['customer_id' => $customer_id])
            ->where('payment_date', '>=', $date_from)
            ->where('payment_date', '<=', $date_to)
            ->select('*', \DB::raw('SUM(amount) as total_amount_paid'))
            ->orderBy('payment_date')
            ->get();
        $returns = ReturnedProduct::groupBy('date')
            ->where(['customer_id' => $customer_id])
            ->where('date', '>=', $date_from)
            ->where('date', '<=', $date_to)
            ->select('*', \DB::raw('SUM(amount) as total_amount_returned'))
            ->orderBy('date')
            ->get();
        // $debts = Transaction::groupBy('entry_date')
        //     ->where('customer_id', $customer_id)
        //     ->where('entry_date', '>=', $date_from)
        //     ->where('entry_date', '<=', $date_to)
        //     ->orderBy('entry_date')
        //     ->get();
        $debts = CustomerDebt::groupBy('created_at')
            ->where('customer_id', $customer_id)
            ->where('created_at', '>=', $date_from)
            ->where('created_at', '<=', $date_to)
            ->orderBy('created_at')
            ->get();
        return array($total_debt_till_date, $past_payments_till_date, $past_returns_till_date, $payments, $returns, $debts);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
