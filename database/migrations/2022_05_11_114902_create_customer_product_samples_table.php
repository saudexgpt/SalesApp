<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerProductSamplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_product_samples', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('visit_id')->nullable();
            $table->integer('product_id');
            $table->double('rate', 10, 2);
            $table->integer('quantity');
            $table->string('packaging');
            $table->string('batch_no');
            $table->string('expiry_date');
            $table->double('amount', 10, 2);
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
        Schema::dropIfExists('customer_product_samples');
    }
}
