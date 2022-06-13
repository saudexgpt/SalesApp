<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\ItemTax;
use App\Models\TeamProduct;
use App\Models\VanInventory;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $items = Item::with(['category', 'price'])->orderBy('name')->get();

        return response()->json(compact('items'));
    }

    public function teamProducts()
    {
        $user = $this->getUser();
        $team_member = $user->memberOfTeam;
        $team_products = [];
        if ($team_member) {
            $team_id = $team_member->team_id;

            $team_products = Item::with('price')->join('team_products', 'items.id', 'team_products.item_id')->where('team_products.team_id', $team_id)->orderBy('items.name')->get();
        }
        return $team_products;
    }

    public function myProducts()
    {
        //
        $user = $this->getUser();
        // $my_products = VanInventory::with(['item.price'])->groupBy('item_id')->where('staff_id', $user->id)->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))->get();

        $all_products = $this->teamProducts(); //Item::with(['category', 'price'])->orderBy('name')->get();

        return response()->json(compact('all_products'));
    }
    public function fetchWarehouseProducts()
    {
        // Create a stream
        set_time_limit(0);
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Content-type: application/x-www-form-urlencoded"
            ]
        ];

        // DOCS: https://www.php.net/manual/en/function.stream-context-create.php
        $context = stream_context_create($opts);

        // Open the file using the HTTP headers set above
        // DOCS: https://www.php.net/manual/en/function.file-get-contents.php
        $products =  file_get_contents('https://gpl.3coretechnology.com/api/get-warehouse-products', false, $context);
        // $products = file_get_contents('http://localhost:8001/api/get-warehouse-products', false, $context);
        $products_in_json =  json_decode($products);
        $items = $products_in_json->items;
        $this->store($items);
        return 'successful';
        // $products = file_get_contents('http://localhost:8080/api/get-warehouse-products');
        // print_r($products);
    }

    public function stockProductsFromWarehouse()
    {
        // $user = $this->getUser();
        // // Create a stream
        // set_time_limit(0);
        // $parameters = [
        //     "rep_ids" => $user->rep_ids
        // ];

        // $params =  http_build_query($parameters);

        // $opts = array(
        //     'http' => [
        //         'method'  => 'POST',
        //         'header'  => 'Content-type: application/x-www-form-urlencoded',
        //         'content' => $params
        //     ]
        // );

        // // DOCS: https://www.php.net/manual/en/function.stream-context-create.php
        // $context = stream_context_create($opts);

        // // Open the file using the HTTP headers set above
        // // DOCS: https://www.php.net/manual/en/function.file-get-contents.php
        // $products =  file_get_contents('https://gpl.3coretechnology.com/api/rep-stock', false, $context);
        // // $products =  file_get_contents('http://localhost:8001/api/rep-stock', false, $context);
        // $products_in_json =  json_decode($products);
        // $items = $products_in_json->items;
        // $this->storeWarehouseStock($user->id, $items);

        return 'success';
        // return $this->showWarehouseStock();
        // $products = file_get_contents('http://localhost:8080/api/get-warehouse-products');
        // print_r($products);
    }
    private function storeWarehouseStock($user_id, $items)
    {
        //
        $user = $this->getUser();
        foreach ($items as $warehouse_item) {
            $id = $warehouse_item->id;
            $waybill_item_id = $warehouse_item->waybill_item_id;
            $waybill_no = $warehouse_item->waybill_item->waybill->waybill_no;
            $invoice_no = $warehouse_item->waybill_item->invoice->invoice_number;
            $item_stock_sub_batch_id = $warehouse_item->item_stock_sub_batch_id;
            $stock = WarehouseStock::where(['dispatched_product_id' => $id, 'waybill_item_id' => $waybill_item_id, 'item_stock_sub_batch_id' => $item_stock_sub_batch_id])->first();
            $item = Item::find($warehouse_item->item_stock->item_id);
            if (!$stock) {
                $stock = new WarehouseStock();
                $stock->dispatched_product_id = $id;
                $stock->waybill_item_id = $waybill_item_id;
                $stock->item_stock_sub_batch_id = $item_stock_sub_batch_id;
                $stock->waybill_no = $waybill_no;
                $stock->invoice_no = $invoice_no;

                $stock->user_id = $user_id;
                $stock->item_id = $warehouse_item->item_stock->item_id;
                $stock->quantity_supplied = $warehouse_item->total_quantity_supplied;
                // $stock->sku = $sku;
                $stock->batch_no = $warehouse_item->item_stock->batch_no;
                $stock->sub_batch_no = $warehouse_item->item_stock->sub_batch_no;
                $stock->expiry_date = $warehouse_item->item_stock->expiry_date;
                $stock->save();

                // $title = "Warehouse products supplied";
                // $description = "$stock->quantity_supplied $item->package_type  of $item->name with Batch No.  $stock->batch_no was sent from warehouse";
                // $this->logUserActivity($title, $description, $user);
            }
            // add item to team products
            $team_member = $user->memberOfTeam;
            if ($team_member) {
                $team_id = $team_member->team_id;
                $this->addTeamroducts($team_id, $item->id);
            }
        }
    }

    private function addTeamroducts($team_id, $item_id)
    {
        $team_product = TeamProduct::where(['team_id' => $team_id, 'item_id' => $item_id])->first();
        if (!$team_product) {
            $team_product = new TeamProduct();
            $team_product->team_id = $team_id;
            $team_product->item_id = $item_id;
            $team_product->save();
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {

        $item = $item->with(['category', 'price'])->find($item->id);
        // $item->currency_id = $item->price->currency_id;
        // $item->purchase_price = $item->price->purchase_price;
        // $item->sale_price = $item->price->sale_price;
        return response()->json(compact('item'), 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store($warehouse_items)
    {
        //
        // $user = $this->getUser();
        foreach ($warehouse_items as $warehouse_item) {
            $id = $warehouse_item->id;
            $name = $warehouse_item->name;
            $category_id = $warehouse_item->category_id;
            $package_type = $warehouse_item->package_type;
            $quantity_per_carton = $warehouse_item->quantity_per_carton;
            $description = $warehouse_item->description;
            $picture = $warehouse_item->picture;
            $item = Item::where('name', $name)->first();
            if (!$item) {
                $item = new Item();
            }

            $item->id = $id;
            $item->name = $name;
            $item->package_type = $package_type;
            $item->quantity_per_carton = $quantity_per_carton;
            $item->category_id = $category_id;
            // $item->sku = $sku;
            $item->description = $description;
            $item->picture = $picture;
            $item->save();

            $item_price = ItemPrice::where('item_id', $item->id)->first();
            if (!$item_price) {
                $item_price = new ItemPrice();
            }
            $item_price->id = $warehouse_item->price->id;
            $item_price->item_id = $warehouse_item->price->item_id;
            $item_price->currency_id = $warehouse_item->price->currency_id;
            $item_price->sale_price = $warehouse_item->price->sale_price;
            // $item_price->purchase_price = $warehouse_item->purchase_price;
            $item_price->save();
        }
    }
}
