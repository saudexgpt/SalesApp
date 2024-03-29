<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitDetailedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visit_detailed_products', function (Blueprint $table) {
            $table->id();
            $table->integer('visit_id');
            $table->integer('customer_id');
            $table->integer('product_id');
            $table->integer('ratings');
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
        Schema::dropIfExists('visit_detailed_products');
    }
}
