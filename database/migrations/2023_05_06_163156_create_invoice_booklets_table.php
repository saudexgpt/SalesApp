<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_booklets', function (Blueprint $table) {
            $table->id();
            $table->integer('rep_id');
            $table->integer('lower_limit');
            $table->integer('upper_limit');
            $table->string('used_invoice_numbers');
            $table->integer('unused_invoice_numbers');
            $table->integer('skipped_invoice_numbers');
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
        Schema::dropIfExists('invoice_booklets');
    }
};
