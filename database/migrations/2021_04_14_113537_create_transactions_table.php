<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->string('invoice_no')->unique();
            $table->integer('team_id');
            $table->integer('field_staff');
            $table->integer('payment_type_id');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('delivery_status')->default('pending');
            $table->double('amount_due', 10, 2);
            $table->integer('approved_by')->nullable();
            $table->date('due_date');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
