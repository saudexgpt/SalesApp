<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDebt extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function sale()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerDebtPayment::class, 'debt_id', 'id');
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'field_staff', 'id');
    }

    public function settleDebt($customer_id)
    {

        $payments = Payment::where('customer_id', $customer_id)->whereRaw('amount - used_to_clear_debt > 0')->orderBy('id')->get();
        if ($payments->isNotEmpty()) {
            foreach ($payments as $payment) {
                $payment_id = $payment->id;
                $amount = $payment->amount;

                $customer_debts = CustomerDebt::where('customer_id', $customer_id)->whereRaw('amount - paid > 0')->orderBy('id')->get();
                if ($customer_debts->isNotEmpty()) {
                    foreach ($customer_debts as $customer_debt) {
                        $debt = $customer_debt->amount - $customer_debt->paid;
                        if ($debt <= $amount) {

                            $customer_debt->paid += $debt;
                            $customer_debt->payment_status = 'paid';
                            $customer_debt->save();

                            $payment->used_to_clear_debt += $debt;
                            $payment->save();

                            $debt_payment = new CustomerDebtPayment();
                            $debt_payment->customer_id = $customer_id;
                            $debt_payment->debt_id = $customer_debt->id;
                            $debt_payment->payment_id = $payment_id;
                            $debt_payment->amount_paid = $debt;
                            $debt_payment->save();

                            $amount -= $debt;
                        } else {
                            $customer_debt->paid += $amount;
                            $customer_debt->save();

                            $payment->used_to_clear_debt += $amount;
                            $payment->save();

                            $debt_payment = new CustomerDebtPayment();
                            $debt_payment->customer_id = $customer_id;
                            $debt_payment->debt_id = $customer_debt->id;
                            $debt_payment->payment_id = $payment_id;
                            $debt_payment->amount_paid = $amount;
                            $debt_payment->save();

                            $amount = 0;
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
        }
    }
}
