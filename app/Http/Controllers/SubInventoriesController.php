<?php

namespace App\Http\Controllers;

use App\Models\SubInventory;
use Illuminate\Http\Request;

class SubInventoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewByProduct(Request $request)
    {
        $item_id = $request->item_id;
        $inventories = SubInventory::with('staff', 'item')
            ->groupBy('staff_id')
            ->where('item_id', $item_id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        return response()->json(compact('inventories'), 200);
    }

    public function viewByStaff(Request $request)
    {
        $staff_id = $request->staff_id;
        $inventories = SubInventory::with('item')
            ->groupBy('item_id')
            ->where('staff_id', $staff_id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        return response()->json(compact('inventories'), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewDetails(Request $request)
    {
        $item_id = $request->item_id;
        $staff_id = $request->staff_id;
        $inventories = SubInventory::with('staff', 'item')
            ->where(['item_id' => $item_id, 'staff_id' => $staff_id])
            ->orderBy('id', 'DESC')
            ->get();
        return response()->json(compact('inventories'), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $this->getUser();
        $inventory_items = json_decode(json_encode($request->inventory_items));
        foreach ($inventory_items as $inventory_item) {
            $sub_inventory = new SubInventory();
            $sub_inventory->staff_id = $inventory_item->staff_id;
            $sub_inventory->item_id = $inventory_item->item_id;
            $sub_inventory->quantity_stocked = $inventory_item->quantity;
            $sub_inventory->balance = $inventory_item->quantity;
            $sub_inventory->stocked_by = $user->name;
            $sub_inventory->save();
        }
        return 'success';
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubInventory  $subInventory
     * @return \Illuminate\Http\Response
     */
    public function show(SubInventory $subInventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SubInventory  $subInventory
     * @return \Illuminate\Http\Response
     */
    public function edit(SubInventory $subInventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SubInventory  $subInventory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SubInventory $subInventory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubInventory  $subInventory
     * @return \Illuminate\Http\Response
     */
    public function destroy(SubInventory $subInventory)
    {
        //
    }
}
