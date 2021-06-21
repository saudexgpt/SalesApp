<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;

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
        }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('delivery_status', 'pending')->orderBy('id', 'DESC')->get();
        return response()->json(compact('orders'), 200);
    }
    public function fetchSales(Request $request)
    {
        $user = $this->getUser();
        $delivery_status = $request->delivery_status;
        $sales = $user->transactions()->with(['customer', 'payments' => function ($q) {
            $q->orderBy('id', 'DESC');
        }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where(['payment_status' => 'paid', 'delivery_status' => $delivery_status])->orderBy('id', 'DESC')->paginate(5);
        return response()->json(compact('sales'), 200);
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
                $invoice->customer_id    = $unsaved_order->customer_id;
                $invoice->field_staff    = $user->id;
                $invoice->payment_status = ($unsaved_order->payment_mode == 'now') ? 'paid' : 'unpaid';
                $invoice->amount_due     = $unsaved_order->amount;
                $invoice->due_date       = ($unsaved_order->payment_mode == 'later') ? date('Y-m-d', strtotime($date)) : date('Y-m-d', strtotime('now'));
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
            // $batches = $item->batches;
            $invoice_item = new TransactionDetail();
            $invoice_item->transaction_id = $invoice->id;
            $invoice_item->product = Item::find($item->item_id)->name;
            $invoice_item->quantity = $item->quantity;
            $invoice_item->packaging = $item->type;
            $invoice_item->rate = $item->rate;
            $invoice_item->amount = $item->amount;
            $invoice_item->save();
        }
    }

    private function makePayments($transaction)
    {

        // $batches = $item->batches;
        $payment = new Payment();
        $payment->transaction_id = $transaction->id;
        $payment->customer_id = $transaction->customer_id;
        $payment->amount = $transaction->amount_due;
        if ($payment->save()) {
            $transaction->amount_paid = $transaction->amount_due;
            $transaction->save();
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
        $quantity_for_supply = $request->quantity_for_supply;
        $quantity = $transaction_detail->quantity;
        $total_quantity_supplied = $quantity_for_supply + $transaction_detail->quantity_supplied;
        if ($total_quantity_supplied <= $quantity) {
            $transaction_detail->quantity_supplied = $total_quantity_supplied;
            $transaction_detail->save();
            if ($transaction_detail->quantity_supplied == $quantity) {
                $transaction_detail->supply_status = 'Completely Supplied';
                $transaction_detail->save();
            } else {
                $transaction_detail->supply_status = 'Partially Supplied';
                $transaction_detail->save();
            }
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
