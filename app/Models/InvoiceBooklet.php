<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceBooklet extends Model
{
    use HasFactory;

    /**
     * Get the user that owns the InvoiceBooklet
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_id', 'id');
    }
    public function updateBookletFields($booklet, $invoice_no)
    {
        $lower_limit = $booklet->lower_limit;

        $booklet->unused_invoice_numbers = deleteSingleElementFromString($booklet->unused_invoice_numbers, $invoice_no);
        $booklet->used_invoice_numbers = addSingleElementToString($booklet->used_invoice_numbers, $invoice_no);
        $booklet->skipped_invoice_numbers = $this->getSkippedInvoiceNumbers($lower_limit, $booklet->used_invoice_numbers);
        $booklet->save();
    }

    private function getSkippedInvoiceNumbers($lower_limit, $used)
    {
        $used_array = explode(',', $used);
        sort($used_array);
        $next_no = $lower_limit;
        $skipped_nos = [];
        foreach ($used_array as $number) {
            if ($number > $next_no) {
                $difference = $number - $next_no;

                for ($i = 1; $i <= $difference; $i++) {
                    $skipped_nos[] = sprintf('%07d', $number - $i);
                }

                $next_no = $number + 1;
            } else {

                $next_no++;
            }
        }
        sort($skipped_nos);
        return implode(',', array_unique($skipped_nos));
    }
}
