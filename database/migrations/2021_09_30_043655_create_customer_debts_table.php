<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDebtsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_debts', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('transaction_id')->nullable();
            $table->double('amount', 10, 2);
            $table->double('paid', 10, 2)->nullable();
            $table->integer('recorded_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_debts');
    }
}
