<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\SubInventory;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VanInventory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function orders()
    {
        $user = $this->getUser();
        // undelivered transactions are considered orders
        $orders = $user->transactions()->with(['customer', 'payments' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'payments.transaction.staff', 'payments.confirmer', 'details'])/*->where('delivery_status', 'pending')*/->orderBy('id', 'DESC')->get();
        return response()->json(compact('orders'), 200);
    }
    public function fetchDebts(Request $request)
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

            $debts = $user->transactions()->groupBy('customer_id')->with(['customer.assignedOfficer', 'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->whereRaw('amount_due - amount_paid > 0')->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->select('*', \DB::raw('SUM(amount_due) as total_amount_due'), \DB::raw('SUM(amount_paid) as total_amount_paid'))->paginate(10);

            // $debts = $user->transactions()->with(['customer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->whereRaw('amount_due - amount_paid > 0')->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        } else {
            $debts = Transaction::groupBy('customer_id')->with(['customer.assignedOfficer', 'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->whereRaw('amount_due - amount_paid > 0')->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->select('*', \DB::raw('SUM(amount_due) as total_amount_due'), \DB::raw('SUM(amount_paid) as total_amount_paid'))->paginate(10);
            // $debts = Transaction::with(['customer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->whereRaw('amount_due - amount_paid > 0')->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('debts', 'currency', 'date_from', 'date_to'), 200);
    }
    public function fetchSales(Request $request)
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

            $sales = $user->transactions()->with(['customer.assignedOfficer', 'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        } else {
            $sales = Transaction::with(['customer.assignedOfficer', 'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('sales', 'currency', 'date_from', 'date_to'), 200);
    }
    public function fetchProductSales(Request $request)
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

            $sales = TransactionDetail::with('transaction.customer')->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                ->join('users', 'transactions.field_staff', 'users.id')->where('transaction_details.field_staff', $user->id)->where('transaction_details.created_at', '<=',  $date_to)->where('transaction_details.created_at', '>=',  $date_from)->where($condition)->orderBy('transaction_details.id', 'DESC')->paginate(10);
        } else {
            $sales = TransactionDetail::with('transaction.customer')->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                ->join('users', 'transactions.field_staff', 'users.id')->where('transaction_details.created_at', '<=',  $date_to)->where('transaction_details.created_at', '>=',  $date_from)->where($condition)->orderBy('transaction_details.id', 'DESC')->paginate(10);
            // $sales = Transaction::with(['customer.assignedOfficer', 'payments' => function ($q) {
            //     $q->orderBy('id', 'DESC');
            // }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('created_at', '<=',  $date_to)->where('created_at', '>=',  $date_from)->where($condition)->orderBy('id', 'DESC')->paginate(10);
        }

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('sales', 'currency', 'date_from', 'date_to'), 200);
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
        $prefix = 'INV-';
        $user = $this->getUser();
        $unsaved_orders = json_decode(json_encode($request->unsaved_orders));
        $order_list = [];
        $unsaved_list = [];
        foreach ($unsaved_orders as $unsaved_order) {
            try {
                $date = $unsaved_order->due_date;
                $invoice_items = json_decode(json_encode($unsaved_order->invoice_items));

                $invoice = new Transaction();
                $customer = Customer::find($unsaved_order->customer_id);
                $invoice->customer_id    = $unsaved_order->customer_id;
                $invoice->field_staff    = $user->id;
                $invoice->payment_status = ($unsaved_order->payment_mode == 'now') ? 'paid' : 'unpaid';
                $invoice->amount_due     = $unsaved_order->amount;
                $invoice->main_amount     = $unsaved_order->main_amount;

                $invoice->due_date       = ($unsaved_order->payment_mode == 'later') ? date('Y-m-d', strtotime($date)) : date('Y-m-d', strtotime('now'));
                $invoice->entry_date = ($request->date) ? date('Y-m-d', strtotime($request->date)) : null;
                if ($invoice->save()) {

                    $invoice->invoice_no = $this->getInvoiceNo($prefix, $invoice->id);
                    $invoice->save();
                }
                // $title = "New order received";
                // $description = "New $invoice->payment_status order ($invoice->invoice_no) was generated by $user->name";
                //log this action to invoice history
                // $this->createInvoiceHistory($invoice, $title, $description);
                //create items invoiceed for
                $this->createInvoiceItems($invoice, $invoice_items);

                if ($unsaved_order->payment_mode == 'now') {
                    $this->makePayments($invoice);
                }

                if (isset($unsaved_order->amount_collected) && $unsaved_order->amount_collected > 0) {
                    $this->payAmount($invoice, $unsaved_order->amount_collected);
                }

                $title = "New Sales made";
                $description = $user->name . " successfully made sales to $customer->business_name";
                $this->logUserActivity($title, $description, $user);


                $order_list[] = $this->show($invoice);
            } catch (\Throwable $th) {
                $unsaved_list[] = $unsaved_order;
            }
        }
        //////update next invoice number/////
        // $this->incrementReceiptNo('invoice');

        //log this activity
        // $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
        // $this->logUserActivity($title, $description, $roles);
        return response()->json(['orders' => $order_list, 'unsaved_list' => $unsaved_list, 'message' => 'success'], 200);
    }

    private function createInvoiceItems($invoice, $invoice_items)
    {
        foreach ($invoice_items as $item) {
            $delivery_mode = $item->delivery_mode;
            $invoice_item = new TransactionDetail();
            $invoice_item->transaction_id = $invoice->id;
            $invoice_item->item_id = $item->item_id;
            $invoice_item->product = Item::find($item->item_id)->name;
            $invoice_item->quantity = $item->quantity;


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
        $date = ($transaction->entry_date != null) ? date('Y-m-d H:i:s', strtotime($transaction->entry_date))  : date('Y-m-d H:i:s', strtotime('now'));;
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
            $description = $user->name . " successfully received NGN$transaction->amount_due from $customer->business_name";
            $this->logUserActivity($title, $description, $user);
        }
    }

    private function payAmount($transaction, $amount)
    {
        $user = $this->getUser();
        $customer_id = $transaction->customer_id;
        $customer = Customer::find($customer_id);
        $customer_transactions = Transaction::where('customer_id', $customer_id)->whereRaw('amount_due - amount_paid > 0')->orderBy('id')->get();
        foreach ($customer_transactions as $customer_transaction) {
            $debt = $customer_transaction->amount_due - $customer_transaction->amount_paid;
            $date = ($customer_transaction->entry_date != null) ? date('Y-m-d H:i:s', strtotime($customer_transaction->entry_date))  : date('Y-m-d H:i:s', strtotime('now'));
            if ($debt <= $amount) {

                $customer_transaction->amount_paid += $debt;
                $customer_transaction->payment_status = 'paid';
                $customer_transaction->save();

                $payment = new Payment();
                $payment->transaction_id = $customer_transaction->id;
                $payment->customer_id = $customer_transaction->customer_id;
                $payment->amount = $debt;
                $payment->payment_date = $date;
                $payment->received_by = $user->id;
                $payment->save();

                $amount -= $debt;
            } else {
                $customer_transaction->amount_paid += $amount;
                $customer_transaction->save();

                $payment = new Payment();
                $payment->transaction_id = $customer_transaction->id;
                $payment->customer_id = $customer_transaction->customer_id;
                $payment->amount = $amount;
                $payment->payment_date = $date;
                $payment->received_by = $user->id;
                $payment->save();
                $amount = 0;
                break;
            }
        }
        if ($amount > 0) {
            $payment = new Payment();
            // $payment->transaction_id = $customer_transaction->id;
            $payment->customer_id = $customer_id;
            $payment->amount = $amount;
            $payment->payment_date = $date;
            $payment->received_by = $user->id;
            $payment->save();
        }
        $title = "Payment Received";
        $description = $user->name . " successfully received NGN$amount from $customer->business_name";
        $this->logUserActivity($title, $description, $user);
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
        $transaction = $transaction->with(['customer', 'details', 'payments' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'payments.transaction.staff', 'payments.confirmer'])->find($transaction->id);
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
            $this->deductFromVanInventory($item_id, $quantity_for_supply);

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
    private function deductFromVanInventory($item_id, $quantity)
    {
        $user = $this->getUser();
        $van_inventories = VanInventory::where(['staff_id' => $user->id, 'item_id' => $item_id])->where('balance', '>', 0)->get();
        $to_supply = $quantity;
        foreach ($van_inventories as $van_inventory) {
            $stock_balance = $van_inventory->balance;
            if ($to_supply <= $stock_balance) {
                $van_inventory->sold += $to_supply;
                $van_inventory->balance -= $to_supply;
                $van_inventory->save();
                $to_supply = 0;
                break;
            } else {
                $van_inventory->sold += $stock_balance;
                $van_inventory->balance = 0;
                $van_inventory->save();
                $to_supply -= $stock_balance;
            }
        }
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
        list($total_debt_till_date, $past_payments_till_date, $payments, $debts) = $this->getCustomerTransactions($customer_id, $date_from, $date_to);

        $past_debt = ($total_debt_till_date) ? $total_debt_till_date->total_amount_due : 0;
        $past_payments = ($past_payments_till_date) ? $past_payments_till_date->total_amount_paid : 0;

        $brought_forward = (int)$past_debt - (int) $past_payments;
        $statements = [];
        if ($payments->isNotEmpty()) {
            foreach ($payments as $payment) {
                //$running_balance += $inbound->quantity;
                $statements[]  = [
                    'type' => 'paid',
                    'amount_transacted' => $payment->total_amount_paid,
                    'description' => '',
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
        if ($debts->isNotEmpty()) {
            foreach ($debts as $debt) {
                //$running_balance -= $outbound->quantity_supplied;
                $statements[] = [
                    'type' => 'debt',
                    'amount_transacted' => $debt->amount_due,
                    'description' => '',
                    'date' => $debt->created_at,
                    'debt' => $debt->amount_due,
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

        $total_debt_till_date = Transaction::groupBy('customer_id')
            ->where('customer_id', $customer_id)
            ->where('created_at', '<', $date_from)
            // ->where('confirmed_by', '!=', null)
            ->select('*', \DB::raw('SUM(amount_due) as total_amount_due'))
            ->first();
        $past_payments_till_date = Payment::groupBy('customer_id')
            ->where('customer_id', $customer_id)
            ->where('created_at', '<', $date_from)
            // ->where('confirmed_by', '!=', null)
            ->select('*', \DB::raw('SUM(amount) as total_amount_paid'))
            ->first();

        $payments = Payment::groupBy('payment_date')
            ->where(['customer_id' => $customer_id])
            ->where('created_at', '>=', $date_from)
            ->where('created_at', '<=', $date_to)
            ->select('*', \DB::raw('SUM(amount) as total_amount_paid'))
            ->orderby('created_at')
            ->get();
        $debts = Transaction::where('customer_id', $customer_id)
            ->where('created_at', '>=', $date_from)
            ->where('created_at', '<=', $date_to)
            ->get();
        return array($total_debt_till_date, $past_payments_till_date, $payments, $debts);
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
