<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Visit;

class SalesRepDetailsRequest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:sales-rep-details-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command makes request for sales rep details from warehouse';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
        $this->storeItems($items);
        // return 'successful';
        // $products = file_get_contents('http://localhost:8080/api/get-warehouse-products');
        // print_r($products);
    }
    public function storeItems($warehouse_items)
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
            $basic_unit = $warehouse_item->basic_unit;
            $basic_unit_quantity_per_package_type = $warehouse_item->basic_unit_quantity_per_package_type;
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
            $item->basic_unit = $basic_unit;
            $item->basic_unit_quantity_per_package_type = $basic_unit_quantity_per_package_type;
            // $item->sku = $sku;
            $item->description = $description;
            $item->picture = $picture;
            $item->save();

            $item_price = ItemPrice::where('item_id', $item->id)->first();
            if (!$item_price) {
                $item_price = new ItemPrice();
            }
            $sale_price = $warehouse_item->price->sale_price;
            if ($item->basic_unit_quantity_per_package_type > 0) {
                $sale_price = $sale_price / $item->basic_unit_quantity_per_package_type;
            }
            $item_price->id = $warehouse_item->price->id;
            $item_price->item_id = $warehouse_item->price->item_id;
            $item_price->currency_id = $warehouse_item->price->currency_id;
            $item_price->unconverted_sale_price = $warehouse_item->price->sale_price;
            $item_price->sale_price = $sale_price;
            // $item_price->purchase_price = $warehouse_item->purchase_price;
            $item_price->save();
        }
    }
    public function fetchWarehouseRepDetails()
    {
        // Create a stream
        set_time_limit(0);
        // $parameters = [
        //     "rep" => $user_id
        // ];

        // $params =  http_build_query($parameters);

        $opts = array(
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                // 'content' => $params
            ]
        );

        // DOCS: https://www.php.net/manual/en/function.stream-context-create.php
        $context = stream_context_create($opts);

        // Open the file using the HTTP headers set above
        // DOCS: https://www.php.net/manual/en/function.file-get-contents.php
        // $customers =  file_get_contents('http://localhost:8001/api/fetch-reps-details', false, $context);
        $customers =  file_get_contents('https://gpl.3coretechnology.com/api/fetch-reps-details', false, $context);
        // $products =  file_get_contents('http://localhost:8001/api/rep-stock', false, $context);
        $customers_in_json =  json_decode($customers);
        $reps = $customers_in_json->reps;
        $this->storeReps($reps);
    }
    private function storeReps($reps)
    {
        //
        foreach ($reps as $rep) {
            $id = $rep->id;
            $repUser = $rep->user;
            if ($repUser) {

                $email = $repUser->email;
                $name_array = explode(' ', $repUser->name);
                $last_name = $name_array[0];
                $first_name = (isset($name_array[1])) ? $name_array[1] : NULL;
                $user = User::where('email', $email)->first();
                if (!$user) {
                    $user = new User();
                    $user->password = bcrypt('password');
                    $user->password_status = 'default';
                    $user->rep_ids = addSingleElementToString('', $id);
                }
                $user->rep_ids = addSingleElementToString($user->rep_ids, $id);
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->name = $repUser->name;
                $user->username = $email;
                $user->email = $email;
                $user->phone = $repUser->phone;
                $user->user_type = 'staff';

                if ($user->save()) {
                    // $role = Role::where('name', $request->role)->first();
                    $user->syncRoles(['sales_rep']);
                    $user->flushCache();
                }
            }
        }
    }
    public function updateAllEmptyCustomerAddresses()
    {
        $customers = Customer::whereRaw('longitude IS NULL')->where('location_updated', '0')->get();
        foreach ($customers as $customer) {
            $first_visit = Visit::where('customer_id', $customer->id)->whereRaw('rep_latitude IS NOT NULL')->first();
            $lat = $first_visit->rep_latitude;
            $long = $first_visit->rep_longitude;

            list($lat, $long, $formatted_address, $street, $area) = getLocationFromLatLong($lat, $long);
            $customer->latitude =  $lat;
            $customer->longitude = $long;
            $customer->address = $formatted_address;
            $customer->street = $street;
            $customer->area = $area;
            $customer->location_updated = '1';
            $customer->save();
        }
    }
    public function updateAllEmptyVisitAddresses()
    {
        $visits = Visit::where('address', '=', NULL)->get();
        foreach ($visits as $visit) {
            $lat = $visit->rep_latitude;
            $long = $visit->rep_longitude;
            if ($lat !== NULL && $long !== NULL) {
                list($lat, $long, $formatted_address, $street, $area) = getLocationFromLatLong($lat, $long);
                $visit->address = $formatted_address;
                $visit->save();
            }
        }
    }
    public function handle()
    {
        //
        $this->fetchWarehouseProducts();
        $this->updateAllEmptyVisitAddresses();
        $this->fetchWarehouseRepDetails();
        $this->updateAllEmptyCustomerAddresses();
    }
}
