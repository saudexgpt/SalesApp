<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerReport;
use App\Models\DailyReport;
use App\Models\HospitalReport;
use App\Models\ManagerDomain;
use App\Models\ManagerLogin;
use App\Models\Payment;
use App\Models\ReturnedProduct;
use App\Models\Schedule;
use App\Models\TeamMember;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Visit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    protected $start_date = '2022-08-01 12:00:00'; // the date the app started being in use
    //
    public function visitedCustomers(Request $request)
    {
        $user = $this->getUser();
        $date = date('Y-m-d', strtotime('now'));
        if (isset($request->date)) {
            $date = date('Y-m-d', strtotime($request->date));
        }
        // $existing_report = DailyReport::where('date', 'LIKE', '%' . $date . '%')
        //     ->where('report_by', $user->id)
        //     ->first();
        // if (!$existing_report) {
        $message = 'unreported';
        $visits = Visit::with(['details', 'customer' => function ($q) {
            $q->select('id', 'customer_type_id', 'business_name');
        }, 'customer.customerContacts'])->where('visitor', $user->id)->where('visit_date', $date)->get();
        $my_customers = Customer::with('customerContacts')->where('relating_officer', $user->id)->orderBy('business_name')->get();
        return response()->json(compact('message', 'visits', 'my_customers'), 200);
        // }
        // return response()->json(['message' => 'reported'], 200);
    }
    public function myReports(Request $request)
    {
        $user = $this->getUser();
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'month';
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            // $panel = $request->panel;
        }

        $with_array = ['hospitalReports', 'reporter', 'customerReports'];

        if ($user->hasRole('sales_rep')) {


            $daily_reports = DailyReport::with($with_array)->where('report_by', $user->id)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('date', 'DESC')->get();
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            if (isset($request->user_id) && $request->user_id !== '') {
                $daily_reports = DailyReport::with($with_array)->where('report_by', $request->user_id)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('date', 'DESC')->get();
            } else {

                list($sales_reps, $sales_reps_ids) = $this->teamMembers();

                $daily_reports = DailyReport::with($with_array)->whereIn('report_by', $sales_reps_ids)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('date', 'DESC')->get();
            }
        } else {
            $condition = ($request->user_id) ? ['report_by' => $request->user_id] : [];
            $daily_reports = DailyReport::with($with_array)->where($condition)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('date', 'DESC')->get();
        }
        return response()->json(compact('daily_reports'), 200);
    }
    public function reportDetails(Request $request)
    {
        $report_id = $request->report_id;
        $user_id = $request->staff_id;
        $with_array = ['hospitalReports.customer', 'reporter', 'customerReports'];
        $report_details = DailyReport::with($with_array)->find($report_id);
        $sales_details = [];
        $payments = [];
        $report_date = date('Y-m-d', strtotime($report_details->date));
        if ($report_details) {

            $sales_details = Transaction::with(['customer', 'payments' => function ($q) {
                $q->orderBy('id', 'DESC');
            }, 'payments.transaction.staff', 'payments.confirmer', 'details'])->where('field_staff', $user_id)->where('entry_date', 'LIKE', '%' . $report_date . '%')->orderBy('id', 'DESC')->get();

            $payments = Payment::with('customer')->groupBy('customer_id')->where('payment_date', 'LIKE', '%' . $report_date . '%')->where('received_by', $user_id)->select('*', \DB::raw('SUM(amount) as total'))->get();

            $returns = ReturnedProduct::with('customer', 'rep', 'item')->where('date', 'LIKE', '%' . $report_date . '%')->where('stocked_by', $user_id)->get();
        }
        return response()->json(compact('sales_details', 'report_details', 'payments', 'returns'), 200);
    }

    public function store(Request $request)
    {
        $user = $this->getUser();
        $unsaved_daily_reports = json_decode(json_encode($request->unsaved_daily_reports));
        // $order_list = [];
        // $unsaved_list = [];
        foreach ($unsaved_daily_reports as $unsaved_daily_report) {

            $date = date('Y-m-d', strtotime($unsaved_daily_report->date));
            $daily_report = DailyReport::where('date', 'LIKE', '%' . $date . '%')
                ->where('report_by', $user->id)
                ->first();
            if (!$daily_report) {
                // create new Daily report
                $daily_report = new DailyReport();
            }
            $daily_report->report_by = $user->id;
            $daily_report->work_with_manager_check = $unsaved_daily_report->work_with_manager_check;
            $daily_report->time_duration_with_manager = $unsaved_daily_report->time_duration_with_manager;
            $daily_report->relationship_with_manager = $unsaved_daily_report->relationship_with_manager;
            $daily_report->date = date('Y-m-d', strtotime($unsaved_daily_report->date));
            $daily_report->market_feedback = $unsaved_daily_report->market_feedback;

            if ($daily_report->save()) {
                if (isset($unsaved_daily_report->customers_report) && $unsaved_daily_report->customers_report != '') {

                    $customers_report = json_decode(json_encode($unsaved_daily_report->customers_report));
                    $this->saveCustomersReport($daily_report->id, $customers_report);
                }

                if (isset($unsaved_daily_report->hospital_report) && $unsaved_daily_report->hospital_report != '') {

                    $hospital_report = json_decode(json_encode($unsaved_daily_report->hospital_report));
                    $this->saveHospitalReport($daily_report->id, $hospital_report);
                }

                if (isset($unsaved_daily_report->returns_report) && $unsaved_daily_report->returns_report != '') {
                    $returns_report = json_decode(json_encode($unsaved_daily_report->returns_report));
                    $this->saveReturnsReport($returns_report, $date);
                }

                $title = "Daily Report Successfully Submitted";
                $description = ucwords($user->name) . "'s daily report has been submitted successfully for $date";
                $this->logUserActivity($title, $description, $user);
            }
        }

        return $this->myReports($request);
    }
    private function saveCustomersReport($daily_report_id, $customers_report)
    {
        foreach ($customers_report as $customer_report) {
            // $batches = $item->batches;
            $cust_report = new CustomerReport();
            $cust_report->daily_report_id = $daily_report_id;
            $cust_report->customer_id = $customer_report->customer_id;
            // $cust_report->visited = $customer_report->visited;
            // $cust_report->opening_debt = $customer_report->opening_debt;
            $cust_report->save();
        }
    }
    private function saveHospitalReport($daily_report_id, $hospitals_report)
    {
        foreach ($hospitals_report as $hospital_report) {
            if (isset($hospital_report->hospital_visit_details) && $hospital_report->hospital_visit_details !== '') {
                $details = json_decode(json_encode($hospital_report->hospital_visit_details));
                foreach ($details as $detail) {

                    $hosp_report = new HospitalReport();
                    $hosp_report->daily_report_id = $daily_report_id;
                    $hosp_report->customer_id = $hospital_report->customer_id;
                    $hosp_report->follow_up_schedule = $detail->hospital_follow_up_schedule;
                    $hosp_report->marketed_products = implode(',', $detail->marketed_products_to_hospitals);
                    $hosp_report->personnel_contacted = $detail->hospital_contacts;
                    $hosp_report->market_feedback = $detail->hospital_feedback;
                    $hosp_report->save();

                    $this->saveSchedule($hosp_report->customer_id, $hosp_report->follow_up_schedule);
                }
            }
        }
    }
    private function saveReturnsReport($returns_report, $date)
    {
        $user = $this->getUser();
        foreach ($returns_report as $return_report) {
            if (isset($return_report->returns) && $return_report->returns !== '') {

                $details = json_decode(json_encode($return_report->returns));
                foreach ($details as $detail) {

                    $return = new ReturnedProduct();
                    $return->customer_id = $return_report->customer_id;
                    $return->item_id = $detail->product_id;
                    $return->expiry_date = date('Y-m-d', strtotime($detail->expiry_date));
                    $return->stocked_by = $user->id;
                    $return->quantity = $detail->quantity_returned;
                    $return->rate = $detail->rate;
                    $return->batch_no = $detail->batch_no;
                    $return->amount = $detail->amount;
                    $return->reason = $detail->reason;
                    $return->date = $date;
                    $return->save();
                }
            }
        }
    }

    private function saveSchedule($customer_id, $schedule_date)
    {
        $user = $this->getUser();

        $schedule_time = date('H:i:s', strtotime($schedule_date));
        $schedule_date = date('Y-m-d', strtotime($schedule_date));
        $rep = $user->id;
        $note = 'Follow up schedule to hospital';
        $day = date('l', strtotime($schedule_date)); // returns 'Monday' or 'Tuesday' , etc
        $day_num = workingDaysStr($day);
        $schedule = Schedule::where(['customer_id' => $customer_id, 'rep' => $rep, 'schedule_date' => $schedule_date])->first();
        if (!$schedule) {
            $schedule = new Schedule();
            $schedule->day = $day;
            $schedule->day_num = $day_num;
            $schedule->schedule_date = $schedule_date;
            $schedule->schedule_time = $schedule_time;
            $schedule->customer_id = $customer_id;
            $schedule->rep = $rep;
            $schedule->note = $note;
            $schedule->scheduled_by = $user->id;
            $schedule->save();
        }
    }

    public function populateManagerLogin()
    {
        $manager_domains = ManagerDomain::get();

        foreach ($manager_domains as $manager_domain) {
            # code...
        }
    }

    public function managerLogins(Request $request)
    {
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
        }
        $condition = [];
        if (isset($request->user_id) && $request->user_id != '') {
            $condition = ['user_id' => $request->user_id];
        }
        $manager_logins = ManagerLogin::with('user')->where($condition)
            ->where('created_at', '>=',  $date_from)
            ->where('created_at', '<=',  $date_to)
            ->get();
        return response()->json(compact('manager_logins'), 200);
    }

    /////////////////////////DOWNLOADABLES/////////////////////
    public function productSales(Request $request)
    {
        $date_from = $this->start_date; // Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }
        $rep_field_name = 'field_staff';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        $total_sales = 0;
        list($sales_reps, $sales_reps_ids) = $this->allTeamMembers($request->team_id);
        $salesQuery = TransactionDetail::join('transactions', 'transactions.id', 'transaction_details.transaction_id')
            ->join('customers', 'transactions.customer_id', 'customers.id')
            ->join('users', 'transactions.field_staff', 'users.id')
            ->where('transaction_details.created_at', '<=',  $date_to)
            ->where('transaction_details.created_at', '>=',  $date_from)
            ->where($condition)
            ->whereIn('transactions.field_staff', $sales_reps_ids)
            ->orderBy('transaction_details.id', 'DESC')
            ->select('*', 'transaction_details.id as id');

        $total_sales = TransactionDetail::where('created_at', '<=',  $date_to)
            ->where('created_at', '>=',  $date_from)
            ->where($condition)
            ->whereIn('field_staff', $sales_reps_ids)
            ->select(\DB::raw('SUM(amount) as total_amount'))->first();

        $sales = $salesQuery->get();
        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('sales', /*'currency', 'date_from', 'date_to', 'total_sales'*/), 200);
    }

    public function collections(Request $request)
    {
        $date_from = $this->start_date; //Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
        }

        $rep_field_name = 'received_by';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        $delivery_status = $request->delivery_status;
        $total_collections = 0;

        list($sales_reps, $sales_reps_ids) = $this->allTeamMembers($request->team_id);
        $paymentsQuery = Payment::groupBy('payment_date', 'customer_id')
            ->join('customers', 'payments.customer_id', 'customers.id')
            ->join('users', 'customers.relating_officer', 'users.id')
            ->where('payment_date', '<=',  $date_to)
            ->where('payment_date', '>=',  $date_from)
            ->whereIn('received_by', $sales_reps_ids)
            ->where($condition)
            ->orderBy('payments.id', 'DESC')
            ->select('*', 'payments.id as id', \DB::raw('SUM(amount) as total_amount'));

        $total_collections = Payment::where('payment_date', '<=',  $date_to)
            ->where('payment_date', '>=',  $date_from)
            ->where($condition)
            ->whereIn('received_by', $sales_reps_ids)
            ->select(\DB::raw('SUM(amount) as total_amount'))->first();

        $payments = $paymentsQuery->get();
        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('payments', /*'currency', 'total_collections', 'date_from', 'date_to'*/), 200);
    }

    public function debts(Request $request)
    {
        $date_from = $this->start_date; //Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
        }

        $rep_field_name = 'field_staff';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        $delivery_status = $request->delivery_status;
        $total_debts = 0;
        $dated_debts = 0;
        list($sales_reps, $sales_reps_ids) = $this->allTeamMembers($request->team_id);
        $debtsQuery = CustomerDebt::groupBy('customer_id')
            ->join('customers', 'customer_debts.customer_id', 'customers.id')
            ->join('users', 'customer_debts.field_staff', 'users.id')
            ->whereRaw('amount - paid > 0')
            // ->where('customer_debts.created_at', '<=',  $date_to)
            // ->where('customer_debts.created_at', '>=',  $date_from)
            ->whereIn('field_staff', $sales_reps_ids)
            ->where($condition)
            ->orderBy('customer_debts.id', 'DESC')
            ->select('*', 'customer_debts.id as id', \DB::raw('SUM(amount) as total_amount_due'), \DB::raw('SUM(paid) as total_amount_paid'));

        $dated_debts = CustomerDebt::where('created_at', '<=',  $date_to)
            ->where('created_at', '>=',  $date_from)
            ->whereIn('field_staff', $sales_reps_ids)
            ->where($condition)
            ->select(\DB::raw('SUM(amount - paid) as total_amount'))->first();

        $total_debts = CustomerDebt::whereIn('field_staff', $sales_reps_ids)
            ->whereIn('field_staff', $sales_reps_ids)
            ->where($condition)
            ->select(\DB::raw('SUM(amount - paid) as total_amount'))->first();

        $debts = $debtsQuery->get();

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('debts'/*, 'currency', 'total_debts', 'dated_debts', 'date_from', 'date_to'*/), 200);
    }
    public function visits(Request $request)
    {
        $date_from = $this->start_date; //Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
        }
        $condition = [];
        if (isset($request->customer_id) && $request->customer_id != 'all') {
            $condition['customer_id'] = $request->customer_id;
        }
        if (isset($request->rep_id) && $request->rep_id != 'all') {
            $condition['visitor'] = $request->rep_id;
        }
        list($sales_reps, $sales_reps_ids) = $this->allTeamMembers($request->team_id);
        $visitsQuery = Visit::join('customers', 'visits.customer_id', 'customers.id')
            ->join('users', 'visits.visitor', 'users.id')
            ->where('visits.created_at', '<=',  $date_to)
            ->where('visits.created_at', '>=',  $date_from)
            ->where($condition)
            ->whereIn('visits.visitor', $sales_reps_ids)
            ->select('*', 'visits.id as id');
        $visits = $visitsQuery->get();
        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('visits'/*, 'currency', 'date_from', 'date_to'*/), 200);
    }


    public function customers(Request $request)
    {
        $searchParams = $request->all();
        $userQuery = Customer::query();
        $today  = date('Y-m-d', strtotime('now'));
        $relationships = [
            'customerContacts',
            'state',
            'lga',
            'customerType', 'registrar', 'assignedOfficer',

            'lastVisited' => function ($q) {
                $q->orderBy('id', 'DESC');
            },

        ];
        $rep_field_name = 'relating_officer';
        $condition = $this->setQueryConditions($request, $rep_field_name);
        list($sales_reps, $sales_reps_ids) = $this->allTeamMembers($request->team_id);
        $userQuery = $userQuery->join('users', 'customers.relating_officer', 'users.id')
            ->where($condition)
            ->whereIn('relating_officer', $sales_reps_ids)
            ->orderBy('business_name')
            ->select('*', 'customers.id as id');
        $paginate_option = $request->paginate_option;
        $customers = $userQuery->get();
        return response()->json(compact('customers'), 200);
    }

    public function returnedProducts(Request $request)
    {
        $date_from = $this->start_date; // Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'quarter';
        $currency = $this->currency();
        if (isset($request->from, $request->to)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }
        $condition = [];
        if (isset($request->customer_id) && $request->customer_id != 'all') {
            $condition = ['customer_id' => $request->customer_id];
        }
        $delivery_status = $request->delivery_status;
        list($sales_reps, $sales_reps_ids) = $this->allTeamMembers($request->team_id);
        $returnsQuery = ReturnedProduct::join('customers', 'returned_products.customer_id', 'customers.id')
            ->join('users', 'returned_products.stocked_by', 'users.id')
            ->join('items', 'returned_products.item_id', 'items.id')
            ->where('returned_products.created_at', '<=',  $date_to)
            ->where('returned_products.created_at', '>=',  $date_from)
            ->where($condition)
            ->whereIn('stocked_by', $sales_reps_ids)
            ->orderBy('returned_products.id', 'DESC')
            ->select('*', 'returned_products.id as id');
        $returns = $returnsQuery->get();

        $date_from = getDateFormatWords($date_from);
        $date_to = getDateFormatWords($date_to);
        return response()->json(compact('returns', 'date_from', 'date_to'), 200);
    }
    /////////////////////////DOWNLOADABLES/////////////////////
}
