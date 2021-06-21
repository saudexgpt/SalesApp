<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_calls', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->string('phone_no');
            $table->integer('caller');
            $table->date('date')->nullable();
            $table->integer('duration_in_seconds')->nullable();
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
        Schema::dropIfExists('customer_calls');
    }
}
