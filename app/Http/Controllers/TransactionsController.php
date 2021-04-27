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
    public function index()
    {
        $user = $this->getUser();
        // undelivered transactions are considered orders
        $orders = $user->transactions()->with('customer', 'payments')->where('delivery_status', 'pending')->orderBy('id', 'DESC')->get();
        return response()->json(compact('orders'), 200);
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
        $date = $request->due_date;
        $invoice_items = json_decode(json_encode($request->invoice_items));
        $invoice = new Transaction();
        $invoice->customer_id    = $request->customer_id;
        $invoice->field_staff    = $user->id;
        $invoice->payment_status = ($request->payment_mode == 'now') ? 'paid' : 'unpaid';
        $invoice->amount_due     = $request->amount;
        $invoice->due_date       = ($request->payment_mode == 'later') ? date('Y-m-d', strtotime($date)) : null;
        $invoice->save();
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

        if ($request->payment_mode == 'now') {
            $this->makePayments($invoice);
        }
        //////update next invoice number/////
        // $this->incrementReceiptNo('invoice');

        //log this activity
        // $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
        // $this->logUserActivity($title, $description, $roles);
        return $this->show($invoice);
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

    private function makePayments($invoice)
    {

        // $batches = $item->batches;
        $payment = new Payment();
        $payment->transaction_id = $invoice->id;
        $payment->customer_id = $invoice->customer_id;
        $payment->amount = $invoice->amount_due;
        if ($payment->save()) {
            $invoice->amount_paid = $invoice->amount_due;
            $invoice->save();
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
        $transaction = $transaction->with('customer', 'details', 'payments')->find($transaction->id);
        return response()->json(compact('transaction'), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        //
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
