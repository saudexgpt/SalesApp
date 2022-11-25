<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function visitedBy()
    {
        return $this->belongsTo(User::class, 'visitor', 'id');
    }
    public function details()
    {
        return $this->hasMany(VisitDetail::class, 'visit_id', 'id');
    }
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'visit_id', 'id');
    }
    public function customerSamples()
    {
        return $this->hasMany(CustomerProductSample::class, 'visit_id', 'id');
    }
    public function detailings()
    {
        return $this->hasMany(VisitDetailedProduct::class, 'visit_id', 'id');
    }
    public function customerStockBalances()
    {
        return $this->hasMany(CustomerStockBalance::class, 'visit_id', 'id');
    }
    public function contact()
    {
        return $this->belongsTo(CustomerContact::class, 'customer_contact_id', 'id');
    }
    public function visitPartner()
    {
        return $this->belongsTo(User::class, 'visiting_partner_id', 'id');
    }
    public function saveAsVisits($user, $unsaved_visit)
    {
        if (isset($unsaved_visit->rep_coordinate) && $unsaved_visit->rep_coordinate != '') {
            $coordinate_array = explode(',', $unsaved_visit->rep_coordinate);
            $lat = $coordinate_array[0];
            $long = $coordinate_array[1];
        } else {
            $lat = (isset($unsaved_visit->rep_latitude)) ? $unsaved_visit->rep_latitude : NULL;
            $long = (isset($unsaved_visit->rep_longitude)) ? $unsaved_visit->rep_longitude : NULL;
        }

        if (isset($unsaved_visit->manager_coordinate) && $unsaved_visit->manager_coordinate != '') {
            $manager_coordinate_array = explode(',', $unsaved_visit->manager_coordinate);
            $manager_lat = $manager_coordinate_array[0];
            $manager_long = $manager_coordinate_array[1];
        } else {
            $manager_lat = NULL;
            $manager_long =  NULL;
        }
        //foreach ($unsaved_visits as $unsaved_visit) {
        $unsaved_list = '';
        $customer_id = (isset($unsaved_visit->customer_id)) ? $unsaved_visit->customer_id : NULL;
        if ($customer_id !== NULL) {
            try {
                $customer = Customer::find($customer_id);
                //check for first visit where proximity is less than or equal to 100m
                $first_visit = Visit::where('customer_id', $customer_id)->where('proximity', '<=', 500)->first();



                if ($customer->latitude === NULL || (!$first_visit)) {
                    if ($lat !== NULL && $lat !== $customer->latitude) {
                        // check if any such customer exists
                        $exisiting_customer = Customer::where(['business_name' => $customer->business_name, 'latitude' => $lat, 'longitude' => $long])->first();
                        if (!$exisiting_customer) {

                            $customer->latitude = $lat;
                            $customer->longitude = $long;
                            $customer->save();
                        }
                    }
                }
                if ($customer->latitude2 === NULL || (!$first_visit)) {
                    if ($manager_lat !== NULL && $manager_lat !== $customer->latitude2) {
                        // check if any such customer exists
                        $exisiting_customer = Customer::where(['business_name' => $customer->business_name, 'latitude2' => $manager_lat, 'longitude2' => $manager_long])->first();
                        if (!$exisiting_customer) {

                            $customer->latitude2 = $manager_lat;
                            $customer->longitude2 = $manager_long;
                            $customer->save();
                        }
                    }
                }
                $visit_date = (isset($unsaved_visit->visit_date)) ? date('Y-m-d', strtotime($unsaved_visit->visit_date)) : date('Y-m-d', strtotime('now')); // $unsaved_visit->visit_date;
                $customer_contact_id = (isset($unsaved_visit->contact_id)) ? $unsaved_visit->contact_id : null;

                // $purposes = json_decode(json_encode($unsaved_visit->purpose));
                $purpose = (isset($unsaved_visit->purpose)) ? $unsaved_visit->purpose : 'Mere Visit';
                $visit_type = 'off site';
                $distance = NULL;
                if ($lat != NULL && $lat != '') {
                    $location_obj = new UserGeolocation();
                    $location_obj->updateLocation($user->id, $lat, $long);

                    // this distance is in Miles. We are going to convert it to metres
                    $distance = haversineGreatCircleDistanceBetweenTwoPoints(
                        $customer->latitude,
                        $customer->longitude,
                        $lat,
                        $long,
                    );
                    //converting miles to metres
                    $distance = mileToMetre($distance);
                    // we are giving 1000 metre allowance
                    if ($distance < 1000) {
                        $visit_type = 'on site';
                    }
                }

                $date = $visit_date;
                // $visit = Visit::where(['customer_id' => $customer_id, 'visitor' => $user->id, 'visit_date' => $date])->first();

                // if (!$visit) {

                $visit = new Visit();
                $visit->customer_id = $customer_id;
                $visit->visitor = $user->id;
                $visit->visit_date = $date;
                $visit->rep_latitude = $lat;
                $visit->rep_longitude = $long;
                $visit->manager_latitude = $manager_lat;
                $visit->manager_longitude = $manager_long;
                $visit->address = NULL; //$formatted_address;
                $visit->accuracy = (isset($unsaved_visit->accuracy)) ? $unsaved_visit->accuracy : NULL;
                $visit->proximity = $distance;
                $visit->visiting_partner_id = (isset($unsaved_visit->visiting_partner_id)) ? $unsaved_visit->visiting_partner_id : NULL;
                $visit->customer_contact_id = $customer_contact_id;
                $visit->visit_type = $visit_type;
                $visit->purpose = $purpose;
                $visit->description = (isset($unsaved_visit->description)) ? $unsaved_visit->description : NULL;
                $visit->visit_duration = (isset($unsaved_visit->description)) ? $unsaved_visit->visit_duration : NULL;
                // } else {
                //     $visit->purpose = str_replace('~', ',', addSingleElementToString($visit->purpose, $purpose));
                //     $visit->rep_latitude = $lat;
                //     $visit->rep_longitude = $long;
                //     $visit->manager_latitude = $manager_lat;
                //     $visit->manager_longitude = $manager_long;
                // }

                $visit->save();
                // $this->saveVisitDetails($unsaved_visit, $visit);

                if (isset($unsaved_visit->hospital_follow_up_schedule) && $unsaved_visit->hospital_follow_up_schedule != null) {
                    $visit->next_appointment_date = date('Y-m-d H:i:s', strtotime($unsaved_visit->hospital_follow_up_schedule));
                    $visit->save();
                    $this->saveSchedule($customer_id, $unsaved_visit->hospital_follow_up_schedule);
                }

                if (isset($unsaved_visit->prescriptions) && !empty($unsaved_visit->prescriptions)) {
                    $this->savePrescriptions($unsaved_visit, $visit);
                }
                if (isset($unsaved_visit->detailed_products) && !empty($unsaved_visit->detailed_products)) {
                    $this->saveDetailedProducts($unsaved_visit, $visit);
                }
                if (isset($unsaved_visit->stock_balances) && !empty($unsaved_visit->stock_balances)) {
                    $this->saveStockBalances($unsaved_visit, $visit);
                }
                if (isset($unsaved_visit->samples) && !empty($unsaved_visit->samples)) {
                    $this->saveSamples($unsaved_visit, $visit);
                }
            } catch (\Throwable $th) {
                $unsaved_list = $unsaved_visit;
            }
        }
        // }
        return $unsaved_list;
    }
    private function saveDetailedProducts($unsaved_visit, $visit)
    {
        $detailed_products = $unsaved_visit->detailed_products;
        foreach ($detailed_products as $detailed_product) {
            $products_array = json_decode(json_encode($detailed_product->products));
            $detailing = new VisitDetailedProduct();
            $detailing->visit_id = $visit->id;
            $detailing->customer_id = $visit->customer_id;
            $detailing->products = implode(',', $products_array);
            $detailing->contacted_personnel = $detailed_product->contacted_personnel;
            $detailing->comments =  $detailed_product->comments;
            $detailing->save();
        }
    }
    // private function saveDetailedProducts($unsaved_visit, $visit)
    // {
    //     $detailed_products = $unsaved_visit->detailed_products;
    //     foreach ($detailed_products as $detailed_product) {
    //         $detailing = new VisitDetailedProduct();
    //         $detailing->visit_id = $visit->id;
    //         $detailing->customer_id = $visit->customer_id;
    //         $detailing->product_id = $detailed_product->product_id;
    //         $detailing->ratings = $detailed_product->ratings;
    //         $detailing->save();
    //     }
    // }
    private function saveStockBalances($unsaved_visit, $visit)
    {
        $stock_balances = $unsaved_visit->stock_balances;
        foreach ($stock_balances as $stock_balance) {
            $new_stock = new CustomerStockBalance();
            $new_stock->visit_id = $visit->id;
            $new_stock->customer_id = $visit->customer_id;
            $new_stock->product_id = $stock_balance->product_id;
            $new_stock->quantity = $stock_balance->quantity;
            $new_stock->save();
        }
    }
    private function saveSamples($unsaved_visit, $visit)
    {
        $user = $this->getUser();
        $samples = $unsaved_visit->samples;
        foreach ($samples as $sample) {
            $new_sample = new CustomerProductSample();
            $new_sample->visit_id = $visit->id;
            $new_sample->customer_id = $visit->customer_id;
            $new_sample->product_id = $sample->product_id;
            $new_sample->quantity = $sample->quantity;
            $new_sample->rate = $sample->rate;
            $new_sample->packaging = $sample->type;
            $new_sample->batch_no = $sample->batch_no;
            $new_sample->expiry_date = $sample->expiry_date;
            $new_sample->amount = $sample->amount;
            $new_sample->save();
            if ($new_sample->save()) {

                $van_inventory_obj = new VanInventory();
                $van_inventory_obj->deductFromVanInventory($new_sample->product_id, $new_sample->quantity, $user);
            }
        }
    }
    private function savePrescriptions($unsaved_visit, $visit)
    {
        $new_prescriptions = $unsaved_visit->prescriptions;
        foreach ($new_prescriptions as $new_prescription) {
            $prescription = new Prescription();
            $prescription->visit_id = $visit->id;
            $prescription->item_id = $new_prescription->product_id;
            $prescription->quantity = $new_prescription->quantity;
            $prescription->type = $new_prescription->type;
            $prescription->save();
        }
    }
    private function saveSchedule($customer_id, $schedule_date)
    {
        $user = $this->getUser();

        $schedule_time = date('H:i:s', strtotime($schedule_date));
        $schedule_date = date('Y-m-d', strtotime($schedule_date));
        $rep = $user->id;
        $note = 'Appointment Schedule';
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
