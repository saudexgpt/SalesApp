<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\CustomerTypesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegionsController;
use App\Http\Controllers\SchedulesController;
use App\Http\Controllers\TiersController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VisitsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\SubInventoriesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register'])->middleware('permission:create-users');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('user', [AuthController::class, 'user']); //->middleware('permission:read-users');
    });
});

//////////////////////////////// APP APIS //////////////////////////////////////////////
Route::group(['middleware' => 'auth:sanctum'], function () {
    // Protected routes for authenticated users
    Route::get('get-lat-long-location', [CustomersController::class, 'getLatLongLocation']);
    Route::get('fetch-necessary-params', [Controller::class, 'fetchNecessayParams']);
    Route::get('user-notifications', [UsersController::class, 'userNotifications']);

    Route::group(['prefix' => 'customers'], function () {
        Route::get('/sample', [CustomersController::class, 'sampleCustomers'])->middleware('permission:read-customers');
        Route::get('/', [CustomersController::class, 'index'])->middleware('permission:read-customers');
        Route::get('/all', [CustomersController::class, 'all'])->middleware('permission:read-customers');

        Route::post('store', [CustomersController::class, 'store'])->middleware('permission:create-customers');

        Route::put('sample/update/{sample_customer}', [CustomersController::class, 'updateSampleCustomer'])->middleware('permission:create-customers');
        Route::post('add-customer-contact', [CustomersController::class, 'addCustomerContact'])->middleware('permission:create-customers');

        Route::post('save-customer-calls', [CustomersController::class, 'saveCustomerCalls']);

        Route::get('details/{customer}', [CustomersController::class, 'customerDetails']);

        Route::get('fetch', [CustomersController::class, 'myCustomers'])->middleware('permission:read-customers');
        Route::put('verify/{customer}', [CustomersController::class, 'verify'])->middleware('permission:verify-customers');



        // Route::delete('{user}', [UsersController::class, 'destroyCustomer'])->middleware('permission:delete-customers');
    });
    Route::group(['prefix' => 'customer-types'], function () {
        Route::get('fetch', [CustomerTypesController::class, 'fetch']);
    });
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('sales-rep', [DashboardController::class, 'saleRepDashboard']);
        Route::get('transaction-stats', [DashboardController::class, 'transactionStat']);
    });
    Route::group(['prefix' => 'daily-report'], function () {
        Route::get('index', [DailyReportController::class, 'index']);
        Route::get('visited-customers', [DailyReportController::class, 'visitedCustomers']);
        Route::get('my-reports', [DailyReportController::class, 'myReports']);

        Route::post('store', [DailyReportController::class, 'store']);
        Route::get('details', [DailyReportController::class, 'reportDetails']);
    });

    Route::group(['prefix' => 'inventory'], function () {
        Route::get('/', [SubInventoriesController::class, 'index']);
        Route::get('/view-by-product', [SubInventoriesController::class, 'viewByProduct']);
        Route::get('/view-by-staff', [SubInventoriesController::class, 'viewByStaff']);
        Route::get('/view-details', [SubInventoriesController::class, 'viewDetails']);
        Route::post('store', [SubInventoriesController::class, 'store']);
        Route::get('/my-inventory', [SubInventoriesController::class, 'myInventory']);
        Route::put('/stock-van/{subInventory}', [SubInventoriesController::class, 'stockVan']);
        Route::post('stock-product-from-warehouse', [ItemsController::class, 'stockProductsFromWarehouse']);
        Route::put('accept-warehouse-products/{warehouse_stock}', [SubInventoriesController::class, 'acceptWarehouseProducts']);
        Route::get('/warehouse-stock', [SubInventoriesController::class, 'showWarehouseStock']);
    });
    Route::group(['prefix' => 'payments'], function () {
        Route::get('/', [PaymentsController::class, 'index']);
        Route::put('/confirm/{payment}', [PaymentsController::class, 'confirm']);
    });
    Route::group(['prefix' => 'products'], function () {
        Route::get('/', [ItemsController::class, 'index']);
        Route::get('fetch-warehouse-products', [ItemsController::class, 'fetchWarehouseProducts']);
        Route::get('my-products', [ItemsController::class, 'myProducts']);
    });
    Route::group(['prefix' => 'regions'], function () {
        Route::get('index', [RegionsController::class, 'index']);
    });
    Route::group(['prefix' => 'sales'], function () {
        Route::get('orders', [TransactionsController::class, 'orders']);
        Route::get('fetch', [TransactionsController::class, 'fetchSales']);
        Route::get('fetch-product-sales', [TransactionsController::class, 'fetchProductSales']);

        Route::get('fetch-debts', [TransactionsController::class, 'fetchDebts']);


        Route::post('store', [TransactionsController::class, 'store']); //->middleware('permission:create-sales');
        Route::put('supply-orders/{transaction_detail}', [TransactionsController::class, 'supplyOrders']);
    });
    Route::group(['prefix' => 'schedules'], function () {
        Route::get('fetch', [SchedulesController::class, 'index']);
        Route::post('store', [SchedulesController::class, 'store']); //->middleware('permission:create-sales');
    });
    Route::group(['prefix' => 'tiers'], function () {
        Route::get('fetch', [TiersController::class, 'fetch']);
    });
    Route::group(['prefix' => 'users'], function () {

        Route::get('fetch-sales-reps', [UsersController::class, 'fetchSalesReps']);

        Route::get('/', [UsersController::class, 'index'])->middleware('permission:read-users');

        Route::post('/', [UsersController::class, 'store'])->middleware('permission:create-users');
        Route::post('add-bulk-customers', [UsersController::class, 'addBulkCustomers'])->middleware('permission:create-customers');

        Route::get('{user}', [UsersController::class, 'show'])->middleware('permission:read-users');
        Route::put('{user}', [UsersController::class, 'update'])->middleware('permission:update-users');

        Route::put('update-password/{user}', [UsersController::class, 'updatePassword']);

        Route::put('reset-password/{user}', [UsersController::class, 'adminResetUserPassword'])->middleware('permission:update-users');

        Route::delete('{user}', [UsersController::class, 'destroy'])->middleware('permission:delete-users');
    });
    Route::group(['prefix' => 'visits'], function () {
        Route::get('fetch', [VisitsController::class, 'index']);
        Route::post('store', [VisitsController::class, 'store']);
    });

    Route::group(['prefix' => 'reports'], function () {
        Route::get('customer-statement', [TransactionsController::class, 'customerStatement']);
    });
});
