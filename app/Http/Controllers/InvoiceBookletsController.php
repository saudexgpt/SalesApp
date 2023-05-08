<?php

namespace App\Http\Controllers;

use App\Models\InvoiceBooklet;
use Illuminate\Http\Request;

class InvoiceBookletsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (isset($request->rep_id) && $request->rep_id !== 'all') {
            $rep_id = $request->rep_id;
            $invoice_booklets = InvoiceBooklet::with('rep')->where('rep_id', $rep_id)->orderBy('id', 'DESC')->paginate($request->limit);
        } else {

            $invoice_booklets = InvoiceBooklet::with('rep')->paginate($request->limit);
        }
        return response()->json(compact('invoice_booklets'), 200);
    }


    public function fetchRepInvoiceBooklets(Request $request)
    {
        $rep_id = $request->rep_id;
        $invoice_booklets = InvoiceBooklet::with('rep')->where('rep_id', $rep_id)->get();
        return response()->json(compact('invoice_booklets'), 200);
    }
    public function store(Request $request)
    {
        $booklets = json_decode(json_encode($request->booklets));
        $unsaved_list = [];
        foreach ($booklets as $booklet) {
            $upper_limit = $booklet->upper_limit;
            $lower_limit = $booklet->lower_limit;
            try {
                $invoice_booklet = new InvoiceBooklet();
                $invoice_booklet->rep_id = $booklet->rep_id;
                $invoice_booklet->lower_limit = $lower_limit;
                $invoice_booklet->upper_limit = $upper_limit;
                $invoice_booklet->unused_invoice_numbers = implode(',', array_unique(numbersArrayFromRange($lower_limit, $upper_limit)));
                $invoice_booklet->save();
            } catch (\Throwable $th) {
                $unsaved_list[] = $booklet;
            }
        }
        return $unsaved_list;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InvoiceBooklet  $invoiceBooklet
     * @return \Illuminate\Http\Response
     */
    public function show(InvoiceBooklet $invoiceBooklet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InvoiceBooklet  $invoiceBooklet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InvoiceBooklet $invoice_booklet)
    {
        $upper_limit = $request->upper_limit;
        $lower_limit = $request->lower_limit;
        $invoice_booklet->lower_limit = $lower_limit;
        $invoice_booklet->upper_limit = $upper_limit;
        $invoice_booklet->unused_invoice_numbers = implode('~', array_unique(numbersArrayFromRange($lower_limit, $upper_limit)));
        $invoice_booklet->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InvoiceBooklet  $invoiceBooklet
     * @return \Illuminate\Http\Response
     */
    public function destroy(InvoiceBooklet $invoiceBooklet)
    {
        //
    }
}
