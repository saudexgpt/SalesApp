<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'customer_type_id',
        'tier_id',
        'sub_region_id',
        'region_id',
        'business_name',
        'email',
        'phone1',
        'phone2',
        'address',
        'street',
        'area',
        'longitude',
        'latitude',
        'registered_by',
        'relating_officer',
    ];
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }
    public function tier()
    {
        return $this->belongsTo(Tier::class);
    }
    public function subRegion()
    {
        return $this->belongsTo(SubRegion::class);
    }
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function lga()
    {
        return $this->belongsTo(LocalGovernmentArea::class);
    }
    public function registrar()
    {
        return $this->belongsTo(User::class, 'registered_by', 'id');
    }
    public function assignedOfficer()
    {
        return $this->belongsTo(User::class, 'relating_officer', 'id');
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }
    public function customerContacts()
    {
        return $this->hasMany(CustomerContact::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
    public function debts()
    {
        return $this->hasMany(CustomerDebt::class, 'customer_id', 'id');
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id', 'id');
    }
    public function visits()
    {
        return $this->hasMany(Visit::class, 'customer_id', 'id');
    }
    public function lastVisited()
    {
        return $this->hasOne(Visit::class, 'customer_id', 'id');
    }

    public function verifications()
    {
        return $this->hasMany(CustomerVerification::class);
    }
    public function calls()
    {
        return $this->hasMany(CustomerCall::class, 'customer_id', 'id');
    }

    public function scopeConfirmed($query)
    {
        return $query->whereStatus('Confirmed');
    }
    public function scopeProspective($query)
    {
        return $query->whereStatus('Prospective');
    }
    public function scopeVerified($query)
    {
        return $query->where('date_verified', '!=', NULL);
    }
    public function scopeUnverified($query)
    {
        return $query->where('date_verified', NULL);
    }

    public function customerSalesReport(Customer $customer, $year)
    {
        $this_year = ($year == 'now') ? date('Y', strtotime('now')) : $year;
        $customer_id = $customer->id;
        $customer_sales = Transaction::where('customer_id', $customer_id)
            ->where('created_at', 'LIKE', '%' . $this_year . '%')
            ->get();
        $payments = Payment::where('customer_id', $customer_id)
            ->where('payment_date', 'LIKE', '%' . $this_year . '%')
            ->get();
        $monthly_sales_amounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthly_collection_amounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($customer_sales as $customer_sale) {
            $month = (int) date('m', strtotime($customer_sale->created_at));
            $month_index = $month - 1;
            $monthly_sales_amounts[$month_index] += (float) $customer_sale->amount_due;
        }
        foreach ($payments as $payment) {
            $month = (int) date('m', strtotime($payment->payment_date));
            $month_index = $month - 1;
            $monthly_collection_amounts[$month_index] += (float) $payment->amount;
        }
        $series = [
            [
                'name' => 'Sales',
                'data' => $monthly_sales_amounts, //array format
                'color' => '#f39c12',
                // 'stack' => 'Initial Stock'
            ],
            [
                'name' => 'Collections',
                'data' => $monthly_collection_amounts, //array format
                //'color' => '#f39c12',
                //'stack' => 'In Stock'
            ],
            // [
            //     'name' => 'In Stock',
            //     'data' => $balance, //array format
            //     'color' => '#00a65a',
            //     'stack' => 'In Stock'
            // ],
            // [
            //     'name' => 'Supplied',
            //     'data' => $supplied, //array format
            //     'color' => '#DC143C',
            //     'stack' => 'Supplied'
            // ],
        ];
        return $series;
    }
    public function customerDebtReport(Customer $customer)
    {
        $today = date('Y-m-d', strtotime('now'));
        $customer_id = $customer->id;
        $customer_debts = CustomerDebt::where('customer_id', $customer_id)
            ->whereRaw('amount - paid > 0')
            ->get();
        $debts_and_payments = [0];
        foreach ($customer_debts as $customer_debt) {
            $debt = $customer_debt->amount - $customer_debt->paid;
            // if ($customer_debt->due_date <= $today) {
            //     // overdue debt
            //     $debts_and_payments[0] += (float) $debt;
            // } else {

            //     $debts_and_payments[1] += (float) $debt;
            // }
            $debts_and_payments[0] += (float) $debt;
            // $debts_and_payments[2] += (float) $customer_debt->paid;
        }
        // $series = [
        //     [
        //         'name' => 'Debt',
        //         'data' => $debts_and_payments[0], //array format
        //         // 'color' => '#333333',
        //         // 'stack' => 'Initial Stock'
        //     ],
        //     [
        //         'name' => 'Paid',
        //         'data' => $debts_and_payments[1]
        //     ],
        // ];
        return $debts_and_payments;
    }
}
