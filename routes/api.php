<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DashboardController;

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
    Route::group(['prefix' => 'customers'], function () {

        Route::get('fetch', [CustomersController::class, 'index'])->middleware('permission:read-customers');
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('sales-rep', [DashboardController::class, 'saleRepDashboard']);
    });
});
