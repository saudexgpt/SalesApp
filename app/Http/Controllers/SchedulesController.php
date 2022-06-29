<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class SchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = $this->getUser();
        $today = date('Y-m-d', strtotime('now'));
        $schedules = Schedule::with(['scheduledBy', 'rep', 'customer'])
            ->where(function ($q) use ($today) {
                $q->where('schedule_date', '>=', $today);
                $q->orWhere('repeat_schedule', 'yes');
            })
            ->where('rep', $user->id)->orderBy('day_num')->get()->groupBy('day');
        return response()->json(compact('schedules'), 200);
    }

    public function fetchRepsSchedules(Request $request)
    {
        $today = date('Y-m-d', strtotime('now'));
        if (isset($request->rep_id) && $request->rep_id !== 'all') {

            $schedules = Schedule::with(['scheduledBy', 'rep', 'customer'])
                ->where(function ($q) use ($today) {
                    $q->where('schedule_date', '>=', $today);
                    $q->orWhere('repeat_schedule', 'yes');
                })
                ->where('rep', $request->rep_id)
                ->orderBy('day_num')
                ->get();
        } else {
            $schedules = Schedule::with(['scheduledBy', 'rep', 'customer'])
                ->where(function ($q) use ($today) {
                    $q->where('schedule_date', '>=', $today);
                    $q->orWhere('repeat_schedule', 'yes');
                })
                ->orderBy('day_num')
                ->get();
        }
        return response()->json(compact('schedules'), 200);
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
        $schedule_date = date('Y-m-d', strtotime($request->schedule_date));
        $schedule_time = date('H:i:s', strtotime($request->schedule_time));
        $customer_id = $request->customer_id;
        $rep = $request->rep;
        $note = $request->note;
        $repeat_schedule = $request->repeat_schedule;
        $day = date('l', strtotime($request->schedule_date)); // returns 'Monday' or 'Tuesday' , etc
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
            $schedule->repeat_schedule = $repeat_schedule;
            $schedule->scheduled_by = $user->id;
            $schedule->save();
        }
    }
    public function storeRepSchedule(Request $request)
    {
        $user = $this->getUser();
        $schedule_date = date('Y-m-d', strtotime($request->schedule_date));
        $schedule_time = '8:00:00';
        $customer_ids = $request->customer_ids;
        $rep = $request->rep;
        $note = $request->note;
        $repeat_schedule = $request->repeat_schedule;
        $day = date('l', strtotime($request->schedule_date)); // returns 'Monday' or 'Tuesday' , etc
        $day_num = workingDaysStr($day);
        foreach ($customer_ids as $customer_id) {
            $schedule = Schedule::where(['customer_id' => $customer_id, 'rep' => $rep, 'schedule_date' => $schedule_date])->first();
            if (!$schedule) {
                $schedule = new Schedule();
            }

            $schedule->day = $day;
            $schedule->day_num = $day_num;
            $schedule->schedule_date = $schedule_date;
            $schedule->schedule_time = $schedule_time;
            $schedule->customer_id = $customer_id;
            $schedule->rep = $rep;
            $schedule->note = $note;
            $schedule->repeat_schedule = $repeat_schedule;
            $schedule->scheduled_by = $user->id;
            $schedule->save();
            $schedule_time = date('H:i:s', strtotime($schedule_time . ' +30 minutes'));
        }
        return 'success';
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function todaySchedule(Request $request)
    {
        $lat = $request->latitude;
        $long = $request->longitude;
        $today = date('Y-m-d H:i:s', strtotime('now' . ' +1 hour'));
        $day = date('l', strtotime('now' . ' +1 hour'));
        $user = $this->getUser();
        $schedules = $user->mySchedules()->with('customer')->where('schedule_date', $today)->orWhere(function ($q) use ($day) {
            $q->where('repeat_schedule', 'yes');
            $q->where('day', $day);
        })->get();
        $today_schedule = [];
        foreach ($schedules as $schedule) {
            $customer = $schedule->customer;


            $distance = haversineGreatCircleDistanceBetweenTwoPoints(
                $customer->latitude,
                $customer->longitude,
                $lat,
                $long,
            );
            $index = (int) $distance;
            if (isset($today_schedule[$index])) {
                $index++;
            }
            $today_schedule[$index] = $schedule;
        }
        return response()->json(compact('today_schedule'), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Schedule $schedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(Schedule $schedule)
    {
        //
        $schedule->delete();
        return response()->json([], 204);
    }
}
