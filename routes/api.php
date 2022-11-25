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
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\SubInventoriesController;
use App\Http\Controllers\ReturnsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\TeamsController;

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



Route::get('change-to-base64', [CustomersController::class, 'changeToBase64']);
// Route::get('test', [VisitsController::class, 'test']);

Route::get('contact-specialties', [Controller::class, 'contactSpecialties']);

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register'])->middleware('permission:create-users');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('user', [AuthController::class, 'user']); //->middleware('permission:read-users');
    });
});
Route::group(['prefix' => 'report'], function () {
    Route::group(['prefix' => 'download'], function () {
        Route::get('product-sales', [ReportsController::class, 'productSales']);
        Route::get('collections', [ReportsController::class, 'collections']);
        Route::get('debts', [ReportsController::class, 'debts']);
        Route::get('visits', [ReportsController::class, 'visits']);
        Route::get('customers', [ReportsController::class, 'customers']);
        Route::get('returned-products', [ReportsController::class, 'returnedProducts']);
    });
});
//////////////////////////////// APP APIS //////////////////////////////////////////////
Route::group(['middleware' => 'auth:sanctum'], function () {
    // Protected routes for authenticated users
    Route::get('get-lat-long-location', [CustomersController::class, 'getLatLongLocation']);
    Route::get('fetch-necessary-params', [Controller::class, 'fetchNecessayParams']);
    Route::get('user-notifications', [UsersController::class, 'userNotifications']);
    Route::get('notification/mark-as-read', [UsersController::class, 'markNotificationAsRead']);
    Route::post('attach/files', [Controller::class, 'attachFiles']);
    Route::put('log-complaints/{id}', [Controller::class, 'logComplaints']);

    Route::get('location-trails', [UsersController::class, 'showLocationTrails']);
    Route::group(['prefix' => 'acl'], function () {
        Route::get('roles/index', [RolesController::class, 'index']);
        Route::post('roles/save', [RolesController::class, 'store']);
        Route::put('roles/update/{role}', [RolesController::class, 'update']);
        Route::post('roles/assign', [RolesController::class, 'assignRoles']);


        Route::get('permissions/index', [PermissionsController::class, 'index']);
        Route::post('permissions/assign-user', [PermissionsController::class, 'assignUserPermissions']);
        Route::post('permissions/assign-role', [PermissionsController::class, 'assignRolePermissions']);
    });
    Route::group(['prefix' => 'customers'], function () {

        Route::get('/state-lga-customers', [CustomersController::class, 'fetchStateLGACustomers'])->middleware('permission:read-customers');
        Route::get('/sample', [CustomersController::class, 'sampleCustomers'])->middleware('permission:read-customers');
        Route::get('/', [CustomersController::class, 'index'])->middleware('permission:read-customers');
        Route::get('/prospective', [CustomersController::class, 'prospectiveCustomers'])->middleware('permission:read-customers');

        Route::get('/all', [CustomersController::class, 'all'])->middleware('permission:read-customers');

        Route::post('store', [CustomersController::class, 'store'])->middleware('permission:create-customers');
        Route::post('store-bulk', [CustomersController::class, 'storeBulkCustomers'])->middleware('permission:create-customers');
        Route::post('store-bulk-debtors', [CustomersController::class, 'uploadBulkDebt'])->middleware('permission:create-customers');
        Route::post('load-debts', [CustomersController::class, 'loadDebts']);
        Route::delete('delete-debt/{debt}', [CustomersController::class, 'deleteDebt']);


        Route::put('update/{customer}', [CustomersController::class, 'update'])->middleware('permission:update-customers');

        Route::post('add-customer-contact', [CustomersController::class, 'addCustomerContact']);
        // ->middleware('permission:update-customers');

        Route::delete('remove-customer-contact/{contact}', [CustomersController::class, 'removeCustomerContact'])->middleware('permission:create-customers');

        Route::post('save-customer-calls', [CustomersController::class, 'saveCustomerCalls']);

        Route::get('details/{customer}', [CustomersController::class, 'customerDetails']);

        Route::get('fetch', [CustomersController::class, 'myCustomers'])->middleware('permission:read-customers');
        Route::get('rep-customers', [CustomersController::class, 'repCustomers']);

        Route::put('verify/{customer}', [CustomersController::class, 'verify'])->middleware('permission:verify-customers');
        Route::put('confirm/{customer}', [CustomersController::class, 'confirmCustomer'])->middleware('permission:confirm-customers');

        Route::put('assign-field-staff/{relating_officer}', [CustomersController::class, 'assignFieldStaff'])->middleware('permission:assign-field-staff');

        Route::put('unassign-customers-that-are-not-mine/{customer}', [CustomersController::class, 'unassignCustomersThatAreNotMine']);



        Route::delete('remove/{customer}', [CustomersController::class, 'destroy'])->middleware('permission:delete-customers');
        Route::put('report-duplicate/{customer}', [CustomersController::class, 'reportDuplicate']);
    });
    Route::group(['prefix' => 'customer-types'], function () {
        Route::get('fetch', [CustomerTypesController::class, 'fetch']);
    });
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('sales-rep', [DashboardController::class, 'saleRepDashboard']);
        Route::get('manager', [DashboardController::class, 'managerDashboard']);

        Route::get('transaction-stats', [DashboardController::class, 'transactionStat']);
        Route::get('rep-sales-and-debt-report', [DashboardController::class, 'repSalesAndDebtReport']);
    });
    Route::group(['prefix' => 'daily-report'], function () {
        Route::get('index', [ReportsController::class, 'index']);
        Route::get('visited-customers', [ReportsController::class, 'visitedCustomers']);
        Route::get('my-reports', [ReportsController::class, 'myReports']);

        Route::post('store', [ReportsController::class, 'store']);
        Route::get('details', [ReportsController::class, 'reportDetails']);
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
        Route::post('/store-bulk-inventory', [SubInventoriesController::class, 'storeBulkMainInventory']);
    });
    Route::group(['prefix' => 'payments'], function () {
        Route::get('/', [PaymentsController::class, 'index']);
        Route::post('/store', [PaymentsController::class, 'store']);
        Route::put('/confirm/{payment}', [PaymentsController::class, 'confirm']);
        Route::put('update-details/{payment}', [PaymentsController::class, 'updateDetails']);
        Route::get('daily-collections', [PaymentsController::class, 'repsDailyCollections']);
        Route::get('show/{payment}', [PaymentsController::class, 'show']);
    });

    Route::group(['prefix' => 'products'], function () {
        Route::get('/', [ItemsController::class, 'index']);
        Route::get('fetch-warehouse-products', [ItemsController::class, 'fetchWarehouseProducts']);
        Route::get('my-products', [ItemsController::class, 'myProducts']);
        Route::get('rep-products', [ItemsController::class, 'repProducts']);
        Route::post('update-basic-unit', [ItemsController::class, 'updateBulkItemBasicUnit']);
    });
    Route::group(['prefix' => 'regions'], function () {
        Route::get('index', [RegionsController::class, 'index']);
    });
    Route::group(['prefix' => 'returns'], function () {
        Route::post('store', [ReturnsController::class, 'store']);
        Route::get('daily-returned-products', [ReturnsController::class, 'repsDailyReturns']);
        Route::put('confirm/{returned_product}', [ReturnsController::class, 'confirm']);
    });
    Route::group(['prefix' => 'sales'], function () {

        Route::get('daily-sales', [TransactionsController::class, 'repsDailySales']);
        Route::get('orders', [TransactionsController::class, 'orders']);
        Route::get('show/{transaction}', [TransactionsController::class, 'show']);
        Route::get('fetch', [TransactionsController::class, 'fetchSales']);
        Route::get('fetch-product-sales', [TransactionsController::class, 'fetchProductSales']);

        Route::get('fetch-debts', [TransactionsController::class, 'fetchDebts']);

        Route::put('/confirm/{transaction}', [TransactionsController::class, 'confirm']);
        Route::post('store', [TransactionsController::class, 'store']); //->middleware('permission:create-sales');
        Route::put('update-details/{transaction_detail}', [TransactionsController::class, 'updateDetails']);
        Route::put('resolve-complaints/{transaction}', [TransactionsController::class, 'resolveComplaints']);


        Route::put('supply-orders/{transaction_detail}', [TransactionsController::class, 'supplyOrders']);
        // Route::get('customer-sales-report/{customer}', [TransactionsController::class, 'customerSalesReport']);
    });
    Route::group(['prefix' => 'schedules'], function () {
        Route::get('fetch', [SchedulesController::class, 'index']);
        Route::get('fetch-reps', [SchedulesController::class, 'fetchRepsSchedules']);
        Route::post('store-rep-schedule', [SchedulesController::class, 'storeRepSchedule']);
        Route::get('today-schedule', [SchedulesController::class, 'todaySchedule']);
        Route::post('store', [SchedulesController::class, 'store']); //->middleware('permission:create-sales');
        Route::delete('destroy/{schedule}', [SchedulesController::class, 'destroy']);
    });
    Route::group(['prefix' => 'teams'], function () {
        Route::get('/', [TeamsController::class, 'index']);
        Route::post('/store', [TeamsController::class, 'store']);
        Route::put('/update/{team}', [TeamsController::class, 'update']);
        Route::delete('/delete/{team}', [TeamsController::class, 'destroy']);

        Route::get('/members', [TeamsController::class, 'fetchTeamMembers']);
        Route::get('/rep-team-members', [TeamsController::class, 'repTeamMembers']);
        Route::post('/add-members', [TeamsController::class, 'addMembers']);
        Route::delete('/remove-member/{team_member}', [TeamsController::class, 'removeMember']);
        Route::put('/create-team-lead/{team_member}', [TeamsController::class, 'createTeamLead']);

        Route::get('/managers', [TeamsController::class, 'fetchManagers']);
        Route::get('/fetch-managers-types', [TeamsController::class, 'fetchManagerTypes']);
        Route::get('/fetch-reps', [TeamsController::class, 'fetchTeamReps']);


        Route::post('/manager/set-coverage-domain', [TeamsController::class, 'setCoverageDomain']);

        Route::get('rep-managers', [TeamsController::class, 'repsManagers']);

        // Route::post('/add-members', [TeamsController::class, 'addMembers']);
        // Route::delete('/remove-member/{team_member}', [TeamsController::class, 'removeMember']);
        // Route::put('/create-team-lead/{team_member}', [TeamsController::class, 'createTeamLead']);
    });
    Route::group(['prefix' => 'tiers'], function () {
        Route::get('fetch', [TiersController::class, 'fetch']);
    });
    Route::group(['prefix' => 'users'], function () {

        Route::get('all', [UsersController::class, 'allUsers']);
        Route::get('fetch-sales-reps', [UsersController::class, 'fetchSalesReps']);

        Route::get('/', [UsersController::class, 'index'])->middleware('permission:read-users');

        Route::post('/', [UsersController::class, 'store'])->middleware('permission:create-users');
        Route::post('add-bulk-customers', [UsersController::class, 'addBulkCustomers'])->middleware('permission:create-customers');

        Route::get('{user}', [UsersController::class, 'show'])->middleware('permission:read-users');
        Route::put('{user}', [UsersController::class, 'update'])->middleware('permission:update-users');

        Route::put('update-password/{user}', [UsersController::class, 'updatePassword']);

        Route::put('reset-password/{user}', [UsersController::class, 'adminResetUserPassword'])->middleware('permission:update-users');
        Route::put('assign-role/{user}', [UsersController::class, 'assignRole']);
        Route::delete('{user}', [UsersController::class, 'destroy'])->middleware('permission:delete-users');

        Route::post('set-current-location', [UsersController::class, 'setCurrentLocation']);
        Route::post('fetch-reps-details', [UsersController::class, 'fetchWarehouseRepDetails']);
        Route::put('set-product-type/{user}', [UsersController::class, 'setUserProductDealingType']);
    });
    Route::group(['prefix' => 'visits'], function () {
        Route::get('fetch', [VisitsController::class, 'index']);
        Route::post('store', [VisitsController::class, 'store']);
        // Route::get('fetch-hospital-visits', [VisitsController::class, 'fetchHospitalVisits']);
        Route::get('fetch-general-visits', [VisitsController::class, 'fetchGeneralVisits']);
        Route::get('fetch-footprints', [VisitsController::class, 'fetchFootPrints']);
        Route::get('fetch-today-visits', [VisitsController::class, 'repTodayVisits']);
        Route::get('customer-visit-stat', [VisitsController::class, 'customerVisitStat']);
        Route::get('fetch-detailed-products', [VisitsController::class, 'fetchDetailedProducts']);
    });

    Route::group(['prefix' => 'reports'], function () {
        Route::get('customer-statement', [TransactionsController::class, 'customerStatement']);

        Route::get('fetch-returned-products', [ReturnsController::class, 'fetchReturnedProducts']);

        Route::get('downloadables', [ReportsController::class, 'downloadables']);
    });
});
