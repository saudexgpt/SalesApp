<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReport;
use App\Models\DailyReport;
use App\Models\HospitalReport;
use App\Models\Payment;
use App\Models\ReturnedProduct;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\Visit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyReportController extends Controller
{
    //
    public function visitedCustomers(Request $request)
    {
        $user = $this->getUser();
        $date = date('Y-m-d', strtotime('now'));
        if (isset($request->date)) {
            $date = date('Y-m-d', strtotime($request->date));
        }
        $existing_report = DailyReport::where('date', 'LIKE', '%' . $date . '%')
            ->where('report_by', $user->id)
            ->first();
        if (!$existing_report) {
            $message = 'unreported';
            $visits = Visit::with(['details', 'customer' => function ($q) {
                $q->select('id', 'customer_type_id', 'business_name');
            }, 'customer.customerContacts'])->where('visitor', $user->id)->where('visit_date', $date)->get();
            $my_customers = Customer::with('customerContacts')->where('relating_officer', $user->id)->get();
            return response()->json(compact('message', 'visits', 'my_customers'), 200);
        }
        return response()->json(['message' => 'reported'], 200);
    }
    public function myReports(Request $request)
    {
        $user = $this->getUser();
        $date_from = Carbon::now()->startOfMonth();
        $date_to = Carbon::now()->endOfMonth();
        $panel = 'month';
        if (isset($request->from, $request->to, $request->panel)) {
            $date_from = date('Y-m-d', strtotime($request->from)) . ' 00:00:00';
            $date_to = date('Y-m-d', strtotime($request->to)) . ' 23:59:59';
            $panel = $request->panel;
        }

        $with_array = ['hospitalReports', 'reporter', 'customerReports'];

        if ($user->hasRole('sales_rep')) {


            $daily_reports = DailyReport::with($with_array)->where('report_by', $user->id)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('id', 'DESC')->get();
        } else if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // $sales_reps_ids is in array form
            if (isset($request->user_id) && $request->user_id !== '') {
                $daily_reports = DailyReport::with($with_array)->where('report_by', $request->user_id)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('id', 'DESC')->get();
            } else {

                list($sales_reps, $sales_reps_ids) = $this->teamMembers();

                $daily_reports = DailyReport::with($with_array)->whereIn('report_by', $sales_reps_ids)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('id', 'DESC')->get();
            }
        } else {
            $condition = ($request->user_id) ? ['report_by' => $request->user_id] : [];
            $daily_reports = DailyReport::with($with_array)->where($condition)->where('date', '>=', $date_from)->where('date', '<=', $date_to)->orderBy('id', 'DESC')->get();
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
        $date = date('Y-m-d', strtotime($request->date));
        $existing_report = DailyReport::where('date', 'LIKE', '%' . $date . '%')
            ->where('report_by', $user->id)
            ->first();
        if (!$existing_report) {
            // create new Daily report
            $daily_report = new DailyReport();
            $daily_report->report_by = $user->id;
            $daily_report->work_with_manager_check = $request->work_with_manager_check;
            $daily_report->time_duration_with_manager = $request->time_duration_with_manager;
            $daily_report->relationship_with_manager = $request->relationship_with_manager;
            $daily_report->date = $request->date;
            $daily_report->market_feedback = $request->market_feedback;

            if ($daily_report->save()) {
                if (isset($request->customers_report) && $request->customers_report != '') {

                    $customers_report = json_decode(json_encode($request->customers_report));
                    $this->saveCustomersReport($daily_report->id, $customers_report);
                }

                if (isset($request->hospital_report) && $request->hospital_report != '') {

                    $hospital_report = json_decode(json_encode($request->hospital_report));
                    $this->saveHospitalReport($daily_report->id, $hospital_report);
                }

                if (isset($request->returns_report) && $request->returns_report != '') {
                    $returns_report = json_decode(json_encode($request->returns_report));
                    $this->saveReturnsReport($returns_report, $date);
                }

                $title = "Daily Report Successfully Submitted";
                $description = ucwords($user->name) . "'s daily report has been submitted successfully for $date";
                $this->logUserActivity($title, $description, $user);
            }
        }
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
}
