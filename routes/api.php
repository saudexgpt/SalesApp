<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\CustomerTypesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegionsController;
use App\Http\Controllers\TiersController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\VisitsController;

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

        Route::get('user', [AuthController::class, 'user'])->middleware('permission:read-users');
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    // Protected routes for authenticated users
    Route::get('get-lat-long-location', [CustomersController::class, 'getLatLongLocation']);
    Route::group(['prefix' => 'customers'], function () {

        Route::get('fetch', [CustomersController::class, 'index'])->middleware('permission:read-customers');
        Route::post('store', [CustomersController::class, 'store'])->middleware('permission:create-customers');
    });
    Route::group(['prefix' => 'customer-types'], function () {
        Route::get('fetch', [CustomerTypesController::class, 'fetch']);
    });
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('sales-rep', [DashboardController::class, 'saleRepDashboard']);
    });
    Route::group(['prefix' => 'products'], function () {
        Route::get('/', [ItemsController::class, 'index']);
        Route::get('fetch-warehouse-products', [ItemsController::class, 'fetchWarehouseProducts']);
    });

    Route::group(['prefix' => 'regions'], function () {
        Route::get('index', [RegionsController::class, 'index']);
    });
    Route::group(['prefix' => 'sales'], function () {
        Route::get('fetch', [TransactionsController::class, 'index']);
        Route::post('store', [TransactionsController::class, 'store']); //->middleware('permission:create-sales');
    });
    Route::group(['prefix' => 'tiers'], function () {
        Route::get('fetch', [TiersController::class, 'fetch']);
    });
    Route::group(['prefix' => 'visits'], function () {
        Route::get('fetch', [VisitsController::class, 'index']);
    });
});
