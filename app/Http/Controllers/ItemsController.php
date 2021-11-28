<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\ItemTax;
use App\Models\VanInventory;
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

    public function myProducts()
    {
        //
        $user = $this->getUser();
        $products = VanInventory::with(['item.price'])->groupBy('item_id')->where('staff_id', $user->id)->select('*', \DB::raw('SUM(quantity_stocked) as total_stocked'), \DB::raw('SUM(sold) as total_sold'), \DB::raw('SUM(balance) as total_balance'))->get();

        return response()->json(compact('products'));
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
        $products_in_json =  json_decode($products);
        $items = $products_in_json->items;
        $this->store($items);
        return 'successful';
        // $products = file_get_contents('http://localhost:8080/api/get-warehouse-products');
        // print_r($products);
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
