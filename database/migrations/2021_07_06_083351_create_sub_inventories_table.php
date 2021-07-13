<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_inventories', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->integer('item_id');
            $table->integer('quantity_stocked');
            $table->integer('sold');
            $table->integer('balance');
            $table->integer('stocked_by')->nullable();
            $table->integer('warehouse_stock_id')->nullable();
            $table->string('batch_no')->nullable();
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
        Schema::dropIfExists('sub_inventories');
    }
}
