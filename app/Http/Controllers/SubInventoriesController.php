<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\SubInventory;
use App\Models\TeamMember;
use App\Models\VanInventory;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class SubInventoriesController extends Controller
{

    public function fetchTeamProducts()
    {
        $user = $this->getUser();
        if ($user->hasRole('sales_rep')) {

            $team_member = $user->memberOfTeam;
            if ($team_member) {
                $team_id = $team_member->team_id;
                $team_members = TeamMember::where('team_id', $team_id)
                    ->get()
                    ->pluck('user_id')
                    ->toArray();

                $items = SubInventory::groupBy('item_id')
                    ->whereIn('staff_id', $team_members)
                    ->get()
                    ->pluck('item_id')
                    ->toArray();
                foreach ($items as $item_id) {
                    $this->addTeamProducts($team_id, $item_id); // from Controller Class
                }
            }
        }
    }
    public function myInventory()
    {
        $user = $this->getUser();
        // $inventories = SubInventory::with('item')
        //     ->groupBy('item_id')
        //     ->where('staff_id', $user->id)
        //     ->where('balance', '>', 0)
        //     ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
        //     ->get();
        // $sub_inventories = SubInventory::with('item')
        //     ->where('staff_id', $user->id)
        //     ->where('balance', '>', 0)
        //     ->get()
        //     ->groupBy('item_id');
        // return response()->json(compact('inventories', 'sub_inventories'), 200);
        $inventories = SubInventory::with('item.price')
            ->groupBy('item_id', 'batch_no', 'expiry_date')
            ->where('staff_id', $user->id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(moved_to_van) as van_quantity'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        $sub_inventories = VanInventory::with('item.price')
            ->groupBy('item_id', 'batch_no', 'expiry_date')
            ->where('staff_id', $user->id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        $this->fetchTeamProducts();
        return response()->json(compact('inventories', 'sub_inventories'), 200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewByProduct(Request $request)
    {
        $user = $this->getUser();

        $item_id = $request->item_id;
        list($sales_reps, $sales_reps_ids) = $this->teamMembers($request->team_id);

        $inventories = SubInventory::with('staff', 'item')
            ->groupBy('staff_id')
            ->whereIn('staff_id', $sales_reps_ids)
            ->where('item_id', $item_id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(moved_to_van) as van_quantity'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        $sub_inventories = VanInventory::with('staff', 'item')
            ->groupBy('staff_id')
            ->whereIn('staff_id', $sales_reps_ids)
            ->where('item_id', $item_id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        // if (!$user->isSuperAdmin() && !$user->isAdmin()) {
        //     // $sales_reps_ids is in array form
        //     list($sales_reps, $sales_reps_ids) = $this->teamMembers($item_id);

        //     $inventories = SubInventory::with('staff', 'item')
        //         ->groupBy('staff_id')
        //         ->whereIn('staff_id', $sales_reps_ids)
        //         ->where('item_id', $item_id)
        //         ->where('balance', '>', 0)
        //         ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(moved_to_van) as van_quantity'), \DB::raw('SUM(balance) as total_balance'))
        //         ->get();
        //     $sub_inventories = VanInventory::with('staff', 'item')
        //         ->groupBy('staff_id')
        //         ->whereIn('staff_id', $sales_reps_ids)
        //         ->where('item_id', $item_id)
        //         ->where('balance', '>', 0)
        //         ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
        //         ->get();
        // } else {
        //     $inventories = SubInventory::with('staff', 'item')
        //         ->groupBy('staff_id')
        //         ->where('item_id', $item_id)
        //         ->where('balance', '>', 0)
        //         ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(moved_to_van) as van_quantity'), \DB::raw('SUM(balance) as total_balance'))
        //         ->get();
        //     $sub_inventories = VanInventory::with('staff', 'item')
        //         ->groupBy('staff_id')
        //         ->where('item_id', $item_id)
        //         ->where('balance', '>', 0)
        //         ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
        //         ->get();
        // }
        return response()->json(compact('inventories', 'sub_inventories'), 200);
    }

    public function viewByStaff(Request $request)
    {
        $staff_id = $request->staff_id;
        $inventories = SubInventory::with('item')
            ->groupBy('item_id', 'batch_no')
            ->where('staff_id', $staff_id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(moved_to_van) as van_quantity'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        $sub_inventories = VanInventory::with('item')
            ->groupBy('item_id', 'batch_no')
            ->where('staff_id', $staff_id)
            ->where('balance', '>', 0)
            ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
            ->get();
        return response()->json(compact('inventories', 'sub_inventories'), 200);
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
            $sub_inventory->expiry_date = date('Y-m-d', strtotime($inventory_item->expiry_date));
            $sub_inventory->stocked_by = $user->name;
            $sub_inventory->save();
        }
        return 'success';
    }
    public function save(Request $request)
    {
        $user = $this->getUser();
        $sub_inventory = new SubInventory();
        $sub_inventory->staff_id = $request->staff_id;
        $sub_inventory->item_id = $request->item_id;
        $sub_inventory->quantity_stocked = $request->quantity;
        $sub_inventory->balance = $request->quantity;
        $sub_inventory->expiry_date = $request->expiry_date;
        $sub_inventory->stocked_by = $user->name;
        $sub_inventory->save();
        // $inventory_items = json_decode(json_encode($request->inventory_items));
        // foreach ($inventory_items as $inventory_item) {
        //     $sub_inventory = new SubInventory();
        //     $sub_inventory->staff_id = $inventory_item->staff_id;
        //     $sub_inventory->item_id = $inventory_item->item_id;
        //     $sub_inventory->quantity_stocked = $inventory_item->quantity;
        //     $sub_inventory->balance = $inventory_item->quantity;
        //     $sub_inventory->expiry_date = $inventory_item->expiry_date;
        //     $sub_inventory->stocked_by = $user->name;
        //     $sub_inventory->save();
        // }
        return 'success';
    }

    public function acceptWarehouseProducts(Request $request, WarehouseStock $warehouse_stock)
    {
        $user = $this->getUser();
        $sub_inventory = new SubInventory();
        $sub_inventory->staff_id = $user->id;
        $sub_inventory->item_id = $warehouse_stock->item_id;
        $sub_inventory->quantity_stocked = $request->quantity;
        $sub_inventory->balance = $request->quantity;
        $sub_inventory->warehouse_stock_id = $warehouse_stock->id;
        $sub_inventory->batch_no = $warehouse_stock->batch_no;
        $sub_inventory->expiry_date = $warehouse_stock->expiry_date;
        $sub_inventory->stocked_by = $user->name;
        if ($sub_inventory->save()) {
            $warehouse_stock->quantity_approved += $sub_inventory->quantity_stocked;
            $warehouse_stock->save();
        }


        return $this->showWarehouseStock();
    }

    public function showWarehouseStock()
    {
        $user_id = $this->getUser()->id;
        $warehouse_stocks = WarehouseStock::with('item.price')->where('user_id', $user_id)->whereRaw('(quantity_supplied - quantity_approved) > 0')->get();
        return response()->json(compact('warehouse_stocks'), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubInventory  $subInventory
     * @return \Illuminate\Http\Response
     */
    public function stockVan(Request $request, SubInventory $subInventory)
    {
        $user = $this->getUser();
        $quantity = $request->quantity;
        $item_id = $subInventory->item_id;
        $batch_no = $subInventory->batch_no;
        $expiry_date = $subInventory->expiry_date;

        $van_inventory = VanInventory::where([
            'sub_inventory_id' => $subInventory->id,
            'staff_id' => $user->id,
        ])->first();
        if ($van_inventory) {
            $van_inventory->quantity_stocked += $quantity;
            $van_inventory->balance += $quantity;
        } else {
            $van_inventory = new VanInventory();
            $van_inventory->staff_id = $user->id;
            $van_inventory->sub_inventory_id = $subInventory->id;
            $van_inventory->item_id = $item_id;
            $van_inventory->quantity_stocked = $quantity;
            $van_inventory->balance = $quantity;
            $van_inventory->batch_no = $subInventory->batch_no;
            $van_inventory->expiry_date = $subInventory->expiry_date;
        }
        $van_inventory->save();

        // subtract from main inventory
        // $subInventory->moved_to_van = $quantity;
        // $subInventory->balance -= $quantity;
        // $subInventory->save();

        $sub_inventories = SubInventory::where(['item_id' => $item_id, 'batch_no' => $batch_no, 'expiry_date' => $expiry_date, 'staff_id' => $user->id])->whereRaw('balance > 0')->get();
        foreach ($sub_inventories as $sub_inventory) {
            if ($quantity > 0) {

                $balance = $sub_inventory->balance;
                if ($quantity <= $balance) {


                    $sub_inventory->moved_to_van += $quantity;
                    $sub_inventory->balance -= $quantity;
                    $quantity = 0;
                    $sub_inventory->save();
                    break;
                } else {
                    $sub_inventory->moved_to_van += $balance;
                    $sub_inventory->balance -= $balance;
                    $quantity -= $balance;
                    $sub_inventory->save();
                }
            }
        }
        return $this->myInventory();
        // $inventories = SubInventory::with('item.price')
        //     ->groupBy('item_id')
        //     ->where('staff_id', $user->id)
        //     ->where('balance', '>', 0)
        //     ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(moved_to_van) as van_quantity'), \DB::raw('SUM(balance) as total_balance'))
        //     ->get();
        // $sub_inventories = VanInventory::with('item.price')
        //     ->groupBy('item_id')
        //     ->where('staff_id', $user->id)
        //     ->where('balance', '>', 0)
        //     ->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))
        //     ->get();
        // return response()->json(compact('inventories', 'sub_inventories'), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SubInventory  $subInventory
     * @return \Illuminate\Http\Response
     */
    public function storeBulkMainInventory(Request $request)
    {
        set_time_limit(0);
        // $actor = $this->getUser();
        $bulk_data = json_decode(json_encode($request->bulk_data));
        $unsaved_inventory = [];
        $error = [];
        // try {
        foreach ($bulk_data as $data) {
            //try {

            $item_name =  trim($data->ITEM_NAME);

            $quantity = trim($data->QUANTITY);
            $expiry_date =  trim($data->EXPIRY_DATE);
            $staff_id =  trim($data->REP_ID);
            // let's fetch the state_id and lga_id
            if ($quantity > 1) {

                $item = Item::where('name', $item_name)->first();
                if ($item) {
                    $request->item_id = $item->id;
                    $request->quantity = $quantity;
                    $request->staff_id = $staff_id;
                    $request->expiry_date = ($expiry_date !== 'NULL') ? date('Y-m-d', strtotime($expiry_date)) : NULL;
                    $this->save($request);
                }
            }
            // } catch (\Throwable $th) {

            //     $unsaved_inventory[] = $data;
            //     $error[] = $th;
            // }
        }
        return response()->json(compact('unsaved_inventory', 'error'), 200);
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
